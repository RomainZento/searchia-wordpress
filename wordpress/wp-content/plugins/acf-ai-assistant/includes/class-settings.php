<?php
/**
 * Gestion de la page de réglages du plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_Settings {
    
    const OPTION_GROUP = 'acf_ai_settings';
    const PAGE_SLUG = 'acf-ai-assistant';
    
    /**
     * Enregistre les settings WordPress
     */
    public static function register_settings() {
        // Provider
        register_setting(self::OPTION_GROUP, 'acf_ai_provider', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'rag_local'
        ]);
        
        // Clé API OpenAI
        register_setting(self::OPTION_GROUP, 'acf_ai_openai_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        // Clé API Gemini
        register_setting(self::OPTION_GROUP, 'acf_ai_gemini_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        // URL RAG local
        register_setting(self::OPTION_GROUP, 'acf_ai_rag_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'http://localhost:8000'
        ]);
        
        // Section principale
        add_settings_section(
            'acf_ai_main_section',
            __('Configuration de l\'API IA', 'acf-ai-assistant'),
            [self::class, 'render_section_description'],
            self::PAGE_SLUG
        );
        
        // Champ : Choix du provider
        add_settings_field(
            'acf_ai_provider',
            __('Provider IA', 'acf-ai-assistant'),
            [self::class, 'render_provider_field'],
            self::PAGE_SLUG,
            'acf_ai_main_section'
        );
        
        // Champ : Clé OpenAI
        add_settings_field(
            'acf_ai_openai_key',
            __('Clé API OpenAI', 'acf-ai-assistant'),
            [self::class, 'render_openai_key_field'],
            self::PAGE_SLUG,
            'acf_ai_main_section'
        );
        
        // Champ : Clé Gemini
        add_settings_field(
            'acf_ai_gemini_key',
            __('Clé API Gemini', 'acf-ai-assistant'),
            [self::class, 'render_gemini_key_field'],
            self::PAGE_SLUG,
            'acf_ai_main_section'
        );
        
        // Champ : URL RAG
        add_settings_field(
            'acf_ai_rag_url',
            __('URL du RAG local', 'acf-ai-assistant'),
            [self::class, 'render_rag_url_field'],
            self::PAGE_SLUG,
            'acf_ai_main_section'
        );
        
        // Contexte global
        register_setting(self::OPTION_GROUP, 'acf_ai_global_context', [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => ''
        ]);
        
        // Skills prédéfinis
        register_setting(self::OPTION_GROUP, 'acf_ai_skills', [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_skills'],
            'default' => self::get_default_skills()
        ]);
        
        // Section Contexte & Skills
        add_settings_section(
            'acf_ai_context_section',
            __('Contexte & Skills', 'acf-ai-assistant'),
            [self::class, 'render_context_section_description'],
            self::PAGE_SLUG
        );
        
        // Champ : Contexte global
        add_settings_field(
            'acf_ai_global_context',
            __('Contexte global', 'acf-ai-assistant'),
            [self::class, 'render_global_context_field'],
            self::PAGE_SLUG,
            'acf_ai_context_section'
        );
        
        // Champ : Skills
        add_settings_field(
            'acf_ai_skills',
            __('Prompts rapides (Skills)', 'acf-ai-assistant'),
            [self::class, 'render_skills_field'],
            self::PAGE_SLUG,
            'acf_ai_context_section'
        );
    }
    
    /**
     * Skills par défaut
     */
    public static function get_default_skills() {
        return [
            ['name' => 'intro', 'label' => '📝 Rédiger une intro', 'prompt' => 'Génère directement une introduction accrocheuse. Ne fais pas de commentaire, ne propose pas plusieurs options. Écris uniquement le texte final.'],
            ['name' => 'resume', 'label' => '📋 Résumer', 'prompt' => 'Résume ce contenu de manière concise. Écris directement le résumé sans introduction ni explication.'],
            ['name' => 'simplify', 'label' => '✨ Simplifier', 'prompt' => 'Reformule ce texte simplement. Écris directement la version simplifiée, sans commentaire.'],
            ['name' => 'seo', 'label' => '🔍 Optimiser SEO', 'prompt' => 'Réécris ce contenu optimisé pour le SEO. Donne directement le texte final avec les mots-clés intégrés, sans explication.'],
            ['name' => 'translate', 'label' => '🌍 Traduire EN', 'prompt' => 'Traduis en anglais. Donne uniquement la traduction, sans commentaire ni explication.'],
        ];
    }
    
    /**
     * Sanitize les skills
     */
    public static function sanitize_skills($skills) {
        if (!is_array($skills)) {
            return self::get_default_skills();
        }
        
        $sanitized = [];
        foreach ($skills as $skill) {
            if (!empty($skill['name']) && !empty($skill['label']) && !empty($skill['prompt'])) {
                $sanitized[] = [
                    'name' => sanitize_key($skill['name']),
                    'label' => sanitize_text_field($skill['label']),
                    'prompt' => sanitize_textarea_field($skill['prompt']),
                ];
            }
        }
        
        return !empty($sanitized) ? $sanitized : self::get_default_skills();
    }
    
    /**
     * Ajoute la page au menu WordPress
     */
    public static function add_settings_page() {
        add_options_page(
            __('ACF AI Assistant', 'acf-ai-assistant'),
            __('ACF AI Assistant', 'acf-ai-assistant'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render_settings_page']
        );
    }
    
    /**
     * Affiche la page de réglages
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Message de succès si settings sauvegardés
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'acf_ai_messages',
                'acf_ai_message',
                __('Paramètres enregistrés.', 'acf-ai-assistant'),
                'updated'
            );
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('acf_ai_messages'); ?>
            
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Enregistrer', 'acf-ai-assistant'));
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Test de connexion', 'acf-ai-assistant'); ?></h2>
            <p>
                <button type="button" id="acf-ai-test-connection" class="button button-secondary">
                    <?php _e('Tester la connexion', 'acf-ai-assistant'); ?>
                </button>
                <span id="acf-ai-test-result" style="margin-left: 10px;"></span>
            </p>
            
            <script>
            document.getElementById('acf-ai-test-connection').addEventListener('click', async function() {
                const resultEl = document.getElementById('acf-ai-test-result');
                resultEl.textContent = 'Test en cours...';
                resultEl.style.color = '#666';
                
                try {
                    const provider = document.querySelector('input[name="acf_ai_provider"]:checked').value;
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'acf_ai_generate',
                            nonce: '<?php echo wp_create_nonce('acf_ai_generate'); ?>',
                            prompt: 'Test de connexion. Réponds juste "OK".',
                            context: ''
                        })
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        resultEl.textContent = '✅ Connexion réussie !';
                        resultEl.style.color = 'green';
                    } else {
                        resultEl.textContent = '❌ ' + (data.data?.message || 'Erreur');
                        resultEl.style.color = 'red';
                    }
                } catch (err) {
                    resultEl.textContent = '❌ Erreur de connexion';
                    resultEl.style.color = 'red';
                }
            });
            </script>
        </div>
        <?php
    }
    
    public static function render_section_description() {
        echo '<p>' . __('Configurez l\'API IA utilisée pour générer du contenu dans les champs ACF.', 'acf-ai-assistant') . '</p>';
    }
    
    public static function render_provider_field() {
        $current = get_option('acf_ai_provider', 'rag_local');
        $providers = [
            'rag_local' => __('RAG Local (SearchIA)', 'acf-ai-assistant'),
            'openai' => __('OpenAI (GPT-4)', 'acf-ai-assistant'),
            'gemini' => __('Google Gemini', 'acf-ai-assistant'),
        ];
        
        foreach ($providers as $value => $label) {
            printf(
                '<label style="display: block; margin-bottom: 8px;">
                    <input type="radio" name="acf_ai_provider" value="%s" %s>
                    %s
                </label>',
                esc_attr($value),
                checked($current, $value, false),
                esc_html($label)
            );
        }
    }
    
    public static function render_openai_key_field() {
        $value = get_option('acf_ai_openai_key', '');
        printf(
            '<input type="password" name="acf_ai_openai_key" value="%s" class="regular-text" autocomplete="off">
            <p class="description">%s</p>',
            esc_attr($value),
            __('Votre clé API OpenAI (commence par sk-...)', 'acf-ai-assistant')
        );
    }
    
    public static function render_gemini_key_field() {
        $value = get_option('acf_ai_gemini_key', '');
        printf(
            '<input type="password" name="acf_ai_gemini_key" value="%s" class="regular-text" autocomplete="off">
            <p class="description">%s</p>',
            esc_attr($value),
            __('Votre clé API Google Gemini', 'acf-ai-assistant')
        );
    }
    
    public static function render_rag_url_field() {
        $value = get_option('acf_ai_rag_url', 'http://localhost:8000');
        printf(
            '<input type="url" name="acf_ai_rag_url" value="%s" class="regular-text">
            <p class="description">%s</p>',
            esc_attr($value),
            __('URL de votre RAG Engine local (ex: http://localhost:8000)', 'acf-ai-assistant')
        );
    }
    
    public static function render_context_section_description() {
        echo '<p>' . __('Ajoutez du contexte pour aider l\'IA à mieux comprendre vos besoins et configurez les prompts rapides.', 'acf-ai-assistant') . '</p>';
    }
    
    public static function render_global_context_field() {
        $value = get_option('acf_ai_global_context', '');
        ?>
        <textarea name="acf_ai_global_context" rows="6" class="large-text" style="width: 100%; max-width: 600px;"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Instructions globales envoyées à l\'IA pour chaque génération. Ex: "Tu es un rédacteur pour un site e-commerce de mode. Utilise un ton décontracté et engageant."', 'acf-ai-assistant'); ?>
        </p>
        <?php
    }
    
    public static function render_skills_field() {
        $skills = get_option('acf_ai_skills', self::get_default_skills());
        ?>
        <div id="acf-ai-skills-list">
            <?php foreach ($skills as $index => $skill): ?>
            <div class="acf-ai-skill-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <div style="flex: 0 0 120px;">
                    <input type="text" name="acf_ai_skills[<?php echo $index; ?>][name]" value="<?php echo esc_attr($skill['name']); ?>" placeholder="ID" class="regular-text" style="width: 100%;">
                </div>
                <div style="flex: 0 0 150px;">
                    <input type="text" name="acf_ai_skills[<?php echo $index; ?>][label]" value="<?php echo esc_attr($skill['label']); ?>" placeholder="Label (avec emoji)" class="regular-text" style="width: 100%;">
                </div>
                <div style="flex: 1;">
                    <textarea name="acf_ai_skills[<?php echo $index; ?>][prompt]" placeholder="Prompt envoyé à l'IA" rows="2" style="width: 100%;"><?php echo esc_textarea($skill['prompt']); ?></textarea>
                </div>
                <button type="button" class="button acf-ai-remove-skill" style="color: #dc3545;">✕</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="acf-ai-add-skill" class="button button-secondary" style="margin-top: 10px;">
            <?php _e('+ Ajouter un skill', 'acf-ai-assistant'); ?>
        </button>
        <p class="description" style="margin-top: 10px;">
            <?php _e('Ces prompts rapides apparaîtront comme boutons dans la popup de génération.', 'acf-ai-assistant'); ?>
        </p>
        
        <script>
        (function() {
            const list = document.getElementById('acf-ai-skills-list');
            const addBtn = document.getElementById('acf-ai-add-skill');
            
            // Supprimer un skill
            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('acf-ai-remove-skill')) {
                    e.target.closest('.acf-ai-skill-row').remove();
                    reindexSkills();
                }
            });
            
            // Ajouter un skill
            addBtn.addEventListener('click', function() {
                const index = list.querySelectorAll('.acf-ai-skill-row').length;
                const row = document.createElement('div');
                row.className = 'acf-ai-skill-row';
                row.style = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start; padding: 10px; background: #f9f9f9; border-radius: 4px;';
                row.innerHTML = `
                    <div style="flex: 0 0 120px;">
                        <input type="text" name="acf_ai_skills[${index}][name]" placeholder="ID" class="regular-text" style="width: 100%;">
                    </div>
                    <div style="flex: 0 0 150px;">
                        <input type="text" name="acf_ai_skills[${index}][label]" placeholder="Label (emoji)" class="regular-text" style="width: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <textarea name="acf_ai_skills[${index}][prompt]" placeholder="Prompt envoyé à l'IA" rows="2" style="width: 100%;"></textarea>
                    </div>
                    <button type="button" class="button acf-ai-remove-skill" style="color: #dc3545;">✕</button>
                `;
                list.appendChild(row);
            });
            
            function reindexSkills() {
                list.querySelectorAll('.acf-ai-skill-row').forEach((row, index) => {
                    row.querySelectorAll('input, textarea').forEach(input => {
                        input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                    });
                });
            }
        })();
        </script>
        <?php
    }
}
