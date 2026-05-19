<?php
/**
 * Plugin Name: ACF AI Assistant
 * Plugin URI: https://github.com/votre-repo/acf-ai-assistant
 * Description: Ajoute une icône IA aux champs ACF pour générer du contenu via OpenAI, Gemini ou un RAG local.
 * Version: 1.0.0
 * Author: SearchIA Team
 * License: GPL v2 or later
 * Text Domain: acf-ai-assistant
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('ACF_AI_VERSION', '1.0.0');
define('ACF_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACF_AI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principale du plugin
 */
class ACF_AI_Assistant {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once ACF_AI_PLUGIN_DIR . 'includes/class-settings.php';
        require_once ACF_AI_PLUGIN_DIR . 'includes/class-api-handler.php';
    }
    
    private function init_hooks() {
        // Initialiser les settings
        add_action('admin_init', [ACF_AI_Settings::class, 'register_settings']);
        add_action('admin_menu', [ACF_AI_Settings::class, 'add_settings_page']);
        
        // Charger les assets sur les pages d'édition
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_acf_ai_generate', [$this, 'handle_ajax_generate']);
    }
    
    /**
     * Charge les scripts et styles dans l'admin
     */
    public function enqueue_admin_assets($hook) {
        // Charger uniquement sur les pages d'édition
        $allowed_hooks = ['post.php', 'post-new.php'];
        if (!in_array($hook, $allowed_hooks)) {
            return;
        }
        
        // Vérifier si ACF est actif
        if (!class_exists('ACF')) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'acf-ai-style',
            ACF_AI_PLUGIN_URL . 'assets/css/acf-ai-style.css',
            [],
            ACF_AI_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'acf-ai-script',
            ACF_AI_PLUGIN_URL . 'assets/js/acf-ai-script.js',
            ['jquery', 'acf-input'],
            ACF_AI_VERSION,
            true
        );
        
        // Récupérer les skills
        $default_skills = [
            ['name' => 'intro', 'label' => '📝 Intro', 'prompt' => 'Génère directement une introduction accrocheuse. Ne fais pas de commentaire, ne propose pas plusieurs options. Écris uniquement le texte final.'],
            ['name' => 'resume', 'label' => '📋 Résumer', 'prompt' => 'Résume ce contenu de manière concise. Écris directement le résumé sans introduction ni explication.'],
            ['name' => 'simplify', 'label' => '✨ Simplifier', 'prompt' => 'Reformule ce texte simplement. Écris directement la version simplifiée, sans commentaire.'],
            ['name' => 'seo', 'label' => '🔍 SEO', 'prompt' => 'Réécris ce contenu optimisé pour le SEO. Donne directement le texte final avec les mots-clés intégrés, sans explication.'],
            ['name' => 'translate', 'label' => '🌍 EN', 'prompt' => 'Traduis en anglais. Donne uniquement la traduction, sans commentaire ni explication.'],
        ];
        $skills = get_option('acf_ai_skills', $default_skills);
        
        // Passer les données au JS
        wp_localize_script('acf-ai-script', 'acfAiConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acf_ai_generate'),
            'provider' => get_option('acf_ai_provider', 'openai'),
            'targetFields' => ['text', 'textarea', 'wysiwyg'],
            'globalContext' => get_option('acf_ai_global_context', ''),
            'skills' => $skills,
            'i18n' => [
                'generate' => __('Générer avec l\'IA', 'acf-ai-assistant'),
                'generating' => __('Génération en cours...', 'acf-ai-assistant'),
                'error' => __('Erreur de génération', 'acf-ai-assistant'),
                'placeholder' => __('Décrivez ce que vous voulez générer...', 'acf-ai-assistant'),
                'skills_title' => __('Prompts rapides', 'acf-ai-assistant'),
            ]
        ]);
    }
    
    /**
     * Gère la requête AJAX de génération
     */
    public function handle_ajax_generate() {
        // Vérifier le nonce
        if (!check_ajax_referer('acf_ai_generate', 'nonce', false)) {
            wp_send_json_error(['message' => 'Nonce invalide'], 403);
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes'], 403);
        }
        
        // Récupérer le prompt
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Le prompt est vide'], 400);
        }
        
        // Contexte optionnel (titre de l'article)
        $context = sanitize_text_field($_POST['context'] ?? '');
        
        // Appeler l'API
        $handler = new ACF_AI_API_Handler();
        $result = $handler->generate($prompt, $context);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ], 500);
        }
        
        wp_send_json_success([
            'content' => $result
        ]);
    }
}

// Initialiser le plugin
add_action('plugins_loaded', function() {
    ACF_AI_Assistant::get_instance();
});

// Hook d'activation
register_activation_hook(__FILE__, function() {
    // Valeurs par défaut
    add_option('acf_ai_provider', 'rag_local');
    add_option('acf_ai_openai_key', '');
    add_option('acf_ai_gemini_key', '');
    add_option('acf_ai_rag_url', 'http://localhost:8000');
});

// Hook de désactivation
register_deactivation_hook(__FILE__, function() {
    // Ne pas supprimer les options pour conserver la config
});
