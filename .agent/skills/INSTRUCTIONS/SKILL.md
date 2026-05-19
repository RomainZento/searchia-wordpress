Plan de développement du plugin ACF AI Assistant.

## Étape 1 : Architecture du projet

Structure des dossiers :
- `admin/` : réglages
- `includes/` : logique PHP
- `assets/` : JS/CSS

Choix de l'API IA : OpenAI (GPT-4) ou Google (Gemini Flash).

Sécurité : Prévoir l'utilisation des Nonces WordPress pour sécuriser les appels AJAX.

Étape 2 : Création de la structure du Plugin
Crée un dossier nommé acf-ai-assistant dans ton répertoire wp-content/plugins/ avec les fichiers de base :

acf-ai-assistant.php : Le fichier principal avec les en-têtes WordPress.

assets/js/acf-ai-script.js : Le script qui gérera le clic, l'affichage de la popup et l'injection du texte.

assets/css/acf-ai-style.css : Le design de l'icône et de la fenêtre de prompt.

Étape 3 : La page de Réglages (Back-Office)
Il faut un endroit où l'administrateur du site peut configurer l'outil.

Créer une page d'options WordPress (via l'API Settings).

Ajouter un champ texte pour sauvegarder la clé API de l'IA de manière sécurisée.

Optionnel mais recommandé : Un menu déroulant pour choisir les types de champs ACF où afficher l'icône (ex: uniquement textarea et wysiwyg).

Étape 4 : Injection de l'icône IA dans ACF (Le Coeur de l'UX)
C'est ici qu'on utilise la puissance d'ACF pour modifier l'interface de saisie.

En PHP : Utiliser le hook acf/render_field_settings pour attacher des attributs, ou charger tes scripts uniquement sur les pages d'édition (hook admin_enqueue_scripts).

En JavaScript : Utiliser l'API JavaScript d'ACF (très puissante). Tu peux écouter l'événement acf.add_action('append', function($el){ ... }) pour repérer quand un champ ACF est affiché à l'écran, puis injecter ton bouton HTML (l'icône d'étincelle) juste à côté du label du champ.

Étape 5 : L'Interface de Prompt (UI/UX)
Lorsque l'utilisateur clique sur l'icône :

Faire apparaître une micro-fenêtre (modale ou dropdown) juste en dessous de l'icône.

Insérer un champ de texte pour le prompt (ex: "Écris une intro accrocheuse") et un bouton "Générer".

Ajouter un indicateur de chargement (spinner) pendant que l'IA réfléchit.

## Étape 6 : La passerelle AJAX et l'appel API

1. Le JS envoie le prompt via `fetch()` à l'action WordPress `wp_ajax_generate_acf_text`
2. Le PHP vérifie les permissions et récupère la clé API
3. Le PHP fait une requête `wp_remote_post()` vers l'API IA
4. L'IA répond et le PHP renvoie le texte en JSON

### Gestion des erreurs

- **Timeout API** : Afficher "La requête a expiré, réessayez"
- **Clé API invalide** : Afficher "Vérifiez votre clé API dans les réglages"
- **Quota dépassé** : Afficher "Quota API dépassé, réessayez plus tard"
- **Réponse invalide** : Logger l'erreur et afficher un message générique

Étape 7 : Insertion du contenu et Finition
Le JavaScript reçoit la réponse de l'IA.

Trouver le champ de saisie (input, textarea ou l'éditeur TinyMCE/Gutenberg de l'ACF concerné).

Injecter le texte à l'intérieur.

Fermer la modale de prompt.

Étape 8 : Tests et Optimisations (V1.1)
Une fois que le flux de base fonctionne, tu peux peaufiner :

Gestion des erreurs : Qu'est-ce qui se passe si la clé API n'est pas bonne ou si le quota est dépassé ? (Afficher un message d'erreur propre).

Contexte automatique : Envoyer le titre de l'article à l'IA en guise de contexte invisible pour que la génération soit plus précise, sans que l'utilisateur ait besoin de le repréciser.

Par quelle étape souhaites-tu commencer ? Si tu veux, on peut regarder ensemble le code PHP nécessaire pour l'Étape 4 (l'injection de l'icône en JS/PHP), qui est souvent la partie la plus spécifique à ACF.