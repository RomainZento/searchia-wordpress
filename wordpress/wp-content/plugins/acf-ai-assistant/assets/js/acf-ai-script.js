/**
 * ACF AI Assistant - Script principal
 * Gère l'injection de l'icône IA et la popup de prompt
 */

(function($) {
    'use strict';

    // Configuration globale (injectée par PHP)
    const config = window.acfAiConfig || {};

    /**
     * Classe principale du chatbot ACF
     */
    class AcfAiAssistant {
        constructor() {
            this.modal = null;
            this.currentField = null;
            this.init();
        }

        init() {
            // Créer la modal une seule fois
            this.createModal();
            
            // Injecter les icônes sur les champs existants
            this.injectIcons();
            
            // Écouter les nouveaux champs ACF (repeater, flexible content)
            if (typeof acf !== 'undefined') {
                acf.addAction('append', ($el) => {
                    this.injectIconsInElement($el);
                });
                
                acf.addAction('ready', () => {
                    this.injectIcons();
                });
            }
        }

        /**
         * Génère le HTML des boutons de skills
         */
        getSkillsHtml() {
            const skills = config.skills || [];
            if (skills.length === 0) return '';
            
            const buttons = skills.map(skill => 
                `<button type="button" class="acf-ai-skill-btn" data-prompt="${this.escapeHtml(skill.prompt)}">${skill.label}</button>`
            ).join('');
            
            return `
                <div class="acf-ai-skills-section">
                    <div class="acf-ai-skills-title">${config.i18n?.skills_title || 'Prompts rapides'}</div>
                    <div class="acf-ai-skills-buttons">${buttons}</div>
                </div>
            `;
        }
        
        /**
         * Escape HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Crée la modal de prompt
         */
        createModal() {
            const skillsHtml = this.getSkillsHtml();
            
            const modalHtml = `
                <div id="acf-ai-modal" class="acf-ai-modal" style="display: none;">
                    <div class="acf-ai-modal-overlay"></div>
                    <div class="acf-ai-modal-content">
                        <div class="acf-ai-modal-header">
                            <span class="acf-ai-modal-title">✨ ${config.i18n?.generate || 'Générer avec l\'IA'}</span>
                            <button type="button" class="acf-ai-modal-close">&times;</button>
                        </div>
                        <div class="acf-ai-modal-body">
                            <div class="acf-ai-field-context" style="display: none;">
                                <small class="acf-ai-context-info"></small>
                            </div>
                            ${skillsHtml}
                            <textarea 
                                id="acf-ai-prompt" 
                                class="acf-ai-prompt-input" 
                                placeholder="${config.i18n?.placeholder || 'Décrivez ce que vous voulez générer...'}"
                                rows="3"
                            ></textarea>
                            <div class="acf-ai-modal-actions">
                                <button type="button" class="button button-primary acf-ai-generate-btn">
                                    <span class="acf-ai-btn-text">Générer</span>
                                    <span class="acf-ai-btn-spinner" style="display: none;">
                                        <span class="spinner is-active"></span>
                                    </span>
                                </button>
                                <button type="button" class="button acf-ai-cancel-btn">Annuler</button>
                            </div>
                            <div class="acf-ai-error" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            this.modal = $('#acf-ai-modal');
            
            // Event listeners
            this.modal.find('.acf-ai-modal-close, .acf-ai-cancel-btn, .acf-ai-modal-overlay').on('click', () => {
                this.closeModal();
            });
            
            this.modal.find('.acf-ai-generate-btn').on('click', () => {
                this.generateContent();
            });
            
            // Skills buttons
            this.modal.find('.acf-ai-skill-btn').on('click', (e) => {
                const prompt = $(e.target).data('prompt');
                this.modal.find('#acf-ai-prompt').val(prompt);
                this.generateContent();
            });
            
            // Générer avec Enter (Shift+Enter pour nouvelle ligne)
            this.modal.find('#acf-ai-prompt').on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.generateContent();
                }
            });
        }

        /**
         * Injecte les icônes sur tous les champs ACF ciblés
         */
        injectIcons() {
            this.injectIconsInElement($(document));
        }

        /**
         * Injecte les icônes dans un élément spécifique
         */
        injectIconsInElement($container) {
            const targetTypes = config.targetFields || ['text', 'textarea', 'wysiwyg'];
            
            targetTypes.forEach(fieldType => {
                $container.find(`.acf-field-${fieldType}`).each((i, el) => {
                    this.addIconToField($(el), fieldType);
                });
            });
        }

        /**
         * Ajoute l'icône IA à un champ
         */
        addIconToField($field, fieldType) {
            // Éviter les doublons
            if ($field.find('.acf-ai-icon').length > 0) {
                return;
            }
            
            // Trouver le conteneur label
            const $labelContainer = $field.find('.acf-label').first();
            if ($labelContainer.length === 0) {
                return;
            }
            
            // Ajouter la classe pour le positionnement
            $field.addClass('acf-ai-enabled');
            
            const iconHtml = `
                <button type="button" class="acf-ai-icon" title="${config.i18n?.generate || 'Générer avec l\'IA'}">
                    ✨
                </button>
            `;
            
            // Ajouter l'icône à la fin du label (sera à droite avec flexbox)
            $labelContainer.append(iconHtml);
            
            // Event listener pour l'icône
            $field.find('.acf-ai-icon').on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openModal($field, fieldType);
            });
        }

        /**
         * Ouvre la modal pour un champ
         */
        openModal($field, fieldType) {
            // Récupérer le contexte du champ ACF
            const fieldName = $field.find('.acf-label label').first().text().trim();
            const fieldDescription = $field.find('.acf-label .description').first().text().trim();
            const fieldKey = $field.data('key') || '';
            
            this.currentField = { 
                element: $field, 
                type: fieldType,
                name: fieldName,
                description: fieldDescription,
                key: fieldKey
            };
            
            // Afficher le contexte du champ
            const $contextInfo = this.modal.find('.acf-ai-context-info');
            const $contextDiv = this.modal.find('.acf-ai-field-context');
            
            if (fieldName || fieldDescription) {
                let contextText = `📌 Champ: <strong>${fieldName || 'Sans nom'}</strong>`;
                if (fieldDescription) {
                    contextText += ` — ${fieldDescription}`;
                }
                $contextInfo.html(contextText);
                $contextDiv.show();
            } else {
                $contextDiv.hide();
            }
            
            this.modal.find('#acf-ai-prompt').val('');
            this.modal.find('.acf-ai-error').hide().text('');
            this.modal.fadeIn(200);
            this.modal.find('#acf-ai-prompt').focus();
        }

        /**
         * Ferme la modal
         */
        closeModal() {
            this.modal.fadeOut(200);
            this.currentField = null;
        }

        /**
         * Génère le contenu via l'API
         */
        async generateContent() {
            const prompt = this.modal.find('#acf-ai-prompt').val().trim();
            
            if (!prompt) {
                this.showError('Veuillez entrer une description.');
                return;
            }
            
            // UI loading
            const $btn = this.modal.find('.acf-ai-generate-btn');
            const $btnText = $btn.find('.acf-ai-btn-text');
            const $btnSpinner = $btn.find('.acf-ai-btn-spinner');
            
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnSpinner.show();
            this.modal.find('.acf-ai-error').hide();
            
            // Construire le contexte enrichi
            const postTitle = $('#title').val() || $('#post-title-0').text() || '';
            const fieldName = this.currentField?.name || '';
            const fieldDescription = this.currentField?.description || '';
            const globalContext = config.globalContext || '';
            
            // Assembler le contexte complet
            let fullContext = '';
            if (globalContext) {
                fullContext += `Instructions: ${globalContext}\n`;
            }
            if (postTitle) {
                fullContext += `Article: ${postTitle}\n`;
            }
            if (fieldName) {
                fullContext += `Champ: ${fieldName}\n`;
            }
            if (fieldDescription) {
                fullContext += `Description du champ: ${fieldDescription}\n`;
            }
            
            try {
                const response = await fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'acf_ai_generate',
                        nonce: config.nonce,
                        prompt: prompt,
                        context: fullContext
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.injectContent(data.data.content);
                    this.closeModal();
                } else {
                    this.showError(data.data?.message || 'Erreur inconnue');
                }
            } catch (error) {
                console.error('ACF AI Error:', error);
                this.showError('Erreur de connexion au serveur.');
            } finally {
                $btn.prop('disabled', false);
                $btnText.show();
                $btnSpinner.hide();
            }
        }

        /**
         * Injecte le contenu généré dans le champ
         */
        injectContent(content) {
            if (!this.currentField) return;
            
            const { element: $field, type } = this.currentField;
            
            switch (type) {
                case 'text':
                    $field.find('input[type="text"]').val(content).trigger('change');
                    break;
                    
                case 'textarea':
                    $field.find('textarea').val(content).trigger('change');
                    break;
                    
                case 'wysiwyg':
                    // TinyMCE editor
                    const $textarea = $field.find('textarea.wp-editor-area');
                    const editorId = $textarea.attr('id');
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                        tinymce.get(editorId).setContent(content);
                    } else {
                        // Fallback si TinyMCE n'est pas initialisé
                        $textarea.val(content);
                    }
                    break;
            }
        }

        /**
         * Affiche une erreur dans la modal
         */
        showError(message) {
            this.modal.find('.acf-ai-error').text(message).show();
        }
    }

    // Initialiser quand le DOM est prêt
    $(document).ready(() => {
        // Vérifier que la config est présente
        if (typeof acfAiConfig !== 'undefined') {
            new AcfAiAssistant();
        }
    });

})(jQuery);
