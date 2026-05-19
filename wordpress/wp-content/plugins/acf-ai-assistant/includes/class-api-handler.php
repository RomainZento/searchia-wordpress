<?php
/**
 * Gestion des appels API vers les différents providers IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_API_Handler {
    
    private $provider;
    
    public function __construct() {
        $this->provider = get_option('acf_ai_provider', 'rag_local');
    }
    
    /**
     * Génère du contenu via l'API configurée
     *
     * @param string $prompt Le prompt utilisateur
     * @param string $context Contexte optionnel (titre article)
     * @return string|WP_Error Le contenu généré ou une erreur
     */
    public function generate(string $prompt, string $context = '') {
        // Ajouter le contexte si présent
        $full_prompt = $prompt;
        if (!empty($context)) {
            $full_prompt = "Contexte: {$context}\n\nDemande: {$prompt}";
        }
        
        switch ($this->provider) {
            case 'openai':
                return $this->call_openai($full_prompt);
            case 'gemini':
                return $this->call_gemini($full_prompt);
            case 'rag_local':
            default:
                return $this->call_rag_local($full_prompt);
        }
    }
    
    /**
     * Appel à l'API OpenAI
     */
    private function call_openai(string $prompt) {
        $api_key = get_option('acf_ai_openai_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Clé API OpenAI non configurée. Allez dans Réglages > ACF AI Assistant.', 'acf-ai-assistant'));
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un assistant de rédaction. Génère du contenu de qualité, clair et bien structuré. Réponds directement sans introduction.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7
            ])
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', __('Erreur de connexion à OpenAI: ', 'acf-ai-assistant') . $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 401) {
            return new WP_Error('invalid_key', __('Clé API OpenAI invalide. Vérifiez votre clé dans les réglages.', 'acf-ai-assistant'));
        }
        
        if ($code === 429) {
            return new WP_Error('quota_exceeded', __('Quota OpenAI dépassé. Réessayez plus tard.', 'acf-ai-assistant'));
        }
        
        if ($code !== 200 || !isset($body['choices'][0]['message']['content'])) {
            $error_msg = $body['error']['message'] ?? __('Réponse invalide d\'OpenAI', 'acf-ai-assistant');
            return new WP_Error('api_error', $error_msg);
        }
        
        return trim($body['choices'][0]['message']['content']);
    }
    
    /**
     * Appel à l'API Google Gemini
     */
    private function call_gemini(string $prompt) {
        $api_key = get_option('acf_ai_gemini_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Clé API Gemini non configurée. Allez dans Réglages > ACF AI Assistant.', 'acf-ai-assistant'));
        }
        
        // Modèle stable et actif
        $model = 'gemini-2.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => "Tu es un assistant de rédaction. Génère du contenu de qualité.\n\n" . $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 1000,
                    'temperature' => 0.7
                ]
            ])
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', __('Erreur de connexion à Gemini: ', 'acf-ai-assistant') . $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 400 || $code === 403) {
            return new WP_Error('invalid_key', __('Clé API Gemini invalide ou permissions insuffisantes.', 'acf-ai-assistant'));
        }
        
        if ($code === 429) {
            return new WP_Error('quota_exceeded', __('Quota Gemini dépassé. Réessayez plus tard.', 'acf-ai-assistant'));
        }
        
        if ($code !== 200 || !isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            $error_msg = $body['error']['message'] ?? __('Réponse invalide de Gemini', 'acf-ai-assistant');
            return new WP_Error('api_error', $error_msg);
        }
        
        return trim($body['candidates'][0]['content']['parts'][0]['text']);
    }
    
    /**
     * Appel au RAG local (SearchIA)
     */
    private function call_rag_local(string $prompt) {
        $base_url = get_option('acf_ai_rag_url', 'http://localhost:8000');
        $base_url = rtrim($base_url, '/');
        
        $url = $base_url . '/ask?query=' . urlencode($prompt);
        
        $response = wp_remote_get($url, [
            'timeout' => 60, // Le RAG local peut être plus lent
        ]);
        
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            
            // Erreur de timeout
            if (strpos($error_msg, 'timed out') !== false) {
                return new WP_Error('timeout', __('La requête a expiré. Vérifiez que le RAG Engine est lancé.', 'acf-ai-assistant'));
            }
            
            // Erreur de connexion
            if (strpos($error_msg, 'Connection refused') !== false) {
                return new WP_Error('connection_refused', __('Impossible de se connecter au RAG. Vérifiez que Docker est lancé.', 'acf-ai-assistant'));
            }
            
            return new WP_Error('api_error', __('Erreur de connexion au RAG: ', 'acf-ai-assistant') . $error_msg);
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code !== 200) {
            $error_msg = $body['message'] ?? __('Erreur du serveur RAG', 'acf-ai-assistant');
            return new WP_Error('api_error', $error_msg);
        }
        
        // Le RAG retourne soit 'answer' soit 'status: Error'
        if (isset($body['status']) && $body['status'] === 'Error') {
            return new WP_Error('rag_error', $body['message'] ?? __('Erreur du RAG Engine', 'acf-ai-assistant'));
        }
        
        if (!isset($body['answer'])) {
            return new WP_Error('invalid_response', __('Réponse invalide du RAG', 'acf-ai-assistant'));
        }
        
        return trim($body['answer']);
    }
}
