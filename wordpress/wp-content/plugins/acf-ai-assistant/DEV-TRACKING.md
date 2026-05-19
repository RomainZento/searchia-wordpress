# ACF AI Assistant - Suivi de développement

## 📋 Informations projet

| Élément | Valeur |
|---------|--------|
| **Nom** | ACF AI Assistant |
| **Version** | 1.0.0 |
| **Branche Git** | btnIa |
| **Date début** | 19 mai 2026 |

---

## 🎯 Spécifications validées

### APIs supportées
- [x] OpenAI (GPT-4)
- [x] Google Gemini
- [x] RAG local (localhost:8000)

### Champs ACF ciblés
- [x] `text`
- [x] `textarea`
- [x] `wysiwyg`

### Fonctionnalités
- [x] Page de réglages (clé API, choix provider)
- [x] Icône ✨ injectable sur les champs ACF
- [x] Popup de prompt avec spinner
- [x] Gestion des erreurs (timeout, clé invalide, quota)
- [x] Sécurité WordPress (nonces)
- [x] **Contexte global** configurable dans les réglages
- [x] **Skills (prompts rapides)** : Intro, Résumer, Simplifier, SEO, Traduire
- [x] **Contexte automatique** : nom du champ, description ACF, titre article

---

## 📁 Structure du plugin

```
acf-ai-assistant/
├── acf-ai-assistant.php          # Fichier principal
├── includes/
│   ├── class-settings.php        # Page de réglages + Skills
│   └── class-api-handler.php     # Gestion multi-API
├── assets/
│   ├── js/acf-ai-script.js       # Icône + popup + Skills + AJAX
│   └── css/acf-ai-style.css      # Styles + Skills
├── DEV-TRACKING.md               # Ce fichier
└── README.md                     # Documentation utilisateur
```

---

## ✅ Avancement

| # | Tâche | Statut | Date |
|---|-------|--------|------|
| 1 | Créer fichier de suivi | ✅ Done | 19/05 |
| 2 | Structure plugin (dossiers) | ✅ Done | 19/05 |
| 3 | Fichier principal PHP | ✅ Done | 19/05 |
| 4 | Page de réglages | ✅ Done | 19/05 |
| 5 | Handler API multi-provider | ✅ Done | 19/05 |
| 6 | Script JS (icône + popup) | ✅ Done | 19/05 |
| 7 | Styles CSS | ✅ Done | 19/05 |
| 8 | Documentation README | ✅ Done | 19/05 |
| 9 | Tests manuels | 🔲 À faire | - |

---

## 📝 Notes de développement

### 19 mai 2026 - Initialisation
- Création de la structure du projet
- Choix validés avec l'utilisateur :
  - 3 providers API (configurable dans settings)
  - Tous les champs texte ACF (text, textarea, wysiwyg)
  - Emplacement : `wordpress/wp-content/plugins/` (versionné)

### 19 mai 2026 - Développement complet
**Fichiers créés :**
- `acf-ai-assistant.php` - Fichier principal avec hooks WordPress
- `includes/class-settings.php` - Page de réglages avec tous les providers
- `includes/class-api-handler.php` - Handler multi-API (OpenAI, Gemini, RAG)
- `assets/js/acf-ai-script.js` - Injection icône + popup + AJAX
- `assets/css/acf-ai-style.css` - Styles avec gradient et animations
- `README.md` - Documentation utilisateur

**Fonctionnalités implémentées :**
- ✅ Icône ✨ sur les champs ACF (text, textarea, wysiwyg)
- ✅ Popup de prompt avec textarea + bouton générer
- ✅ 3 providers : RAG local, OpenAI, Gemini
- ✅ Page de réglages complète avec test de connexion
- ✅ Gestion des erreurs (timeout, clé invalide, quota)
- ✅ Contexte automatique (titre article)
- ✅ Support des Repeaters ACF (via acf.addAction)
- ✅ Sécurité WordPress (nonces, permissions)

---

## 🐛 Bugs connus

*Aucun bug signalé pour le moment.*

---

## 🚀 Prochaines étapes (V1.1)

- [ ] Contexte automatique (titre article envoyé à l'IA)
- [ ] Historique des prompts
- [ ] Raccourcis clavier
- [ ] Support des champs Repeater ACF
