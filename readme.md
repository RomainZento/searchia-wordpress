# SearchIA

Assistant IA local pour WordPress utilisant RAG (Retrieval-Augmented Generation).

## 📋 Déploiement sur un nouvel environnement

### ⚠️ Prérequis

- **Docker** et **Docker Compose** installés
- **Ollama** installé sur la machine hôte

### Étape 1 : Installer et démarrer Ollama

```bash
# macOS avec Homebrew
brew install ollama

# Ou télécharger depuis https://ollama.com/download
```

Lancez Ollama dans un terminal séparé (doit rester actif) :
```bash
ollama serve
```

Téléchargez le modèle `llama3` :
```bash
ollama pull llama3
```

### Étape 2 : Lancer les conteneurs Docker

```bash
docker compose up -d
```

Cela va automatiquement :
- Créer la base de données MySQL
- Installer WordPress (accessible sur http://localhost:8080)
- Lancer le RAG Engine (accessible sur http://localhost:8000)
- Lancer Qdrant (accessible sur http://localhost:6333)

### Étape 3 : Configurer WordPress

1. Accédez à http://localhost:8080
2. Suivez l'assistant d'installation WordPress
3. Créez vos articles de blog

### Étape 4 : Installer le plugin Chatbot

Copiez le plugin chatbot dans le dossier WordPress :
```bash
cp -r wordpress/wp-content/plugins/mon-chatbot-ai wordpress_data/wp-content/plugins/
```

Puis activez-le dans **WordPress Admin → Extensions → Mon Chatbot RAG Local**.

### Étape 5 : Indexer les articles pour le RAG

Une fois vos articles créés, lancez l'indexation :
```bash
curl http://localhost:8000/ingest
```

Le chat IA est maintenant prêt à répondre aux questions sur vos articles !

## 📁 Structure du projet

```
searchIA/
├── docker-compose.yml      # Orchestration des services
├── rag-engine/             # Code Python du RAG
│   ├── Dockerfile
│   ├── main.py
│   └── requirements.txt
├── wordpress/              # Plugins/thèmes WordPress custom (versionné)
│   └── wp-content/plugins/
│       └── mon-chatbot-ai/ # Widget de chat IA
├── data/                   # (ignoré) Données MySQL locales
└── wordpress_data/         # (ignoré) Installation WordPress locale
```

> **Note :** Les dossiers `data/` et `wordpress_data/` sont **ignorés par Git** car ils sont régénérés automatiquement par Docker au premier lancement.

## 📝 Commandes utiles

| Action | Commande |
|--------|----------|
| Démarrer les conteneurs | `docker compose up -d` |
| Arrêter les conteneurs | `docker compose down` |
| Voir les logs | `docker compose logs -f` |
| Indexer les articles | `curl http://localhost:8000/ingest` |
| Tester le RAG | `curl "http://localhost:8000/ask?query=bonjour"` |
| Vider l'index Qdrant | `curl -X DELETE http://localhost:6333/collections/wordpress_posts` |

## 🔧 Dépannage

### Le chat répond "Désolé, je n'ai pas compris"
→ Vérifiez qu'Ollama est lancé (`ollama serve`)
→ Vérifiez que llama3 est installé (`ollama list`)

### Erreur de connexion à Ollama
→ Assurez-vous qu'Ollama tourne sur le port 11434
→ Redémarrez le conteneur rag-engine : `docker compose restart rag-engine`
