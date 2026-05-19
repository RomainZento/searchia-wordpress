# ACF AI Assistant

Plugin WordPress qui ajoute une icône ✨ aux champs ACF pour générer du contenu avec l'IA.

## 🎯 Fonctionnalités

- **Icône IA** sur les champs text, textarea et wysiwyg d'ACF
- **3 providers IA** au choix :
  - RAG Local (SearchIA)
  - OpenAI (GPT-4)
  - Google Gemini
- **Popup de prompt** élégante et responsive
- **Contexte automatique** : le titre de l'article est envoyé à l'IA
- **Gestion des erreurs** complète

## 📦 Installation

1. Copiez le dossier `acf-ai-assistant` dans `wp-content/plugins/`
2. Activez le plugin dans WordPress > Extensions
3. Configurez l'API dans **Réglages > ACF AI Assistant**

## ⚙️ Configuration

### RAG Local (par défaut)

Si vous utilisez SearchIA avec Docker :
- URL : `http://localhost:8000` (ou `http://rag-engine:8000` depuis le conteneur WordPress)

### OpenAI

1. Créez une clé API sur [platform.openai.com](https://platform.openai.com/api-keys)
2. Collez la clé dans les réglages
3. Sélectionnez "OpenAI (GPT-4)" comme provider

### Google Gemini

1. Créez une clé API sur [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Collez la clé dans les réglages
3. Sélectionnez "Google Gemini" comme provider

## 🚀 Utilisation

1. Éditez un article avec des champs ACF
2. Cliquez sur l'icône ✨ à côté du label du champ
3. Décrivez ce que vous voulez générer
4. Cliquez sur "Générer" ou appuyez sur Entrée
5. Le contenu est automatiquement inséré dans le champ

## 🔧 Dépannage

### L'icône n'apparaît pas

- Vérifiez qu'ACF est activé
- Vérifiez que vous êtes sur une page d'édition d'article
- Videz le cache du navigateur

### Erreur "Impossible de se connecter au RAG"

- Vérifiez que Docker est lancé (`docker compose up -d`)
- Vérifiez qu'Ollama est lancé (`ollama serve`)

### Erreur "Clé API invalide"

- Vérifiez que votre clé API est correcte dans les réglages
- OpenAI : la clé doit commencer par `sk-`

## 📄 Licence

GPL v2 ou ultérieur
