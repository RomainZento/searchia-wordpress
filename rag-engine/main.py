from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import ChatOllama, OllamaEmbeddings
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from qdrant_client import QdrantClient
from qdrant_client.http import models
import pymysql
import os
import traceback
import re

app = FastAPI()

# Configuration CORS - Autorise WordPress et Next.js
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:8080",  # WordPress
        "http://localhost:3000",  # Next.js dev
        "http://127.0.0.1:3000",  # Next.js dev (alt)
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "db"),
    "user": os.getenv("DB_USER", "rag_wordpress"),
    "password": os.getenv("DB_PASS", "bikde7-pydmav-mUqwah"),
    "database": os.getenv("DB_NAME", "db_rag")
}

OLLAMA_BASE_URL = "http://host.docker.internal:11434"
MODEL_NAME = "llama3"
COLLECTION_NAME = "wordpress_posts"
QDRANT_URL = "http://qdrant:6333"

def get_embeddings():
    return OllamaEmbeddings(model=MODEL_NAME, base_url=OLLAMA_BASE_URL)

@app.get("/ingest")
def ingest_wordpress_data():
    try:
        connection = pymysql.connect(**DB_CONFIG)
        cursor = connection.cursor(pymysql.cursors.DictCursor)
        cursor.execute("SELECT post_title, post_content FROM wp_posts WHERE post_status='publish' AND post_type='post'")
        posts = cursor.fetchall()
        
        client = QdrantClient(url=QDRANT_URL)
        
        # On recrée la collection proprement
        client.recreate_collection(
            collection_name=COLLECTION_NAME,
            vectors_config=models.VectorParams(size=4096, distance=models.Distance.COSINE),
        )

        embeddings_model = get_embeddings()
        
        points = []
        for i, post in enumerate(posts):
            text_clean = re.sub(r'<[^>]+>', '', post['post_content']) # Supprime toutes les balises <...>
            text_clean = re.sub(r'\s+', ' ', text_clean).strip()    # Supprime les espaces en trop
            
            text_content = f"TITRE: {post['post_title']}\nCONTENU: {text_clean}"
            vector = embeddings_model.embed_query(text_content)
            points.append(models.PointStruct(
                id=i, 
                vector=vector, 
                payload={"text": text_content}
            ))
        
        client.upsert(collection_name=COLLECTION_NAME, points=points)
        return {"status": "Success", "indexed": len(posts)}
        
    except Exception as e:
        print(traceback.format_exc())
        return {"status": "Error", "message": str(e)}

@app.get("/ask")
def ask_question(query: str):
    try:
        # Initialisation explicite
        client = QdrantClient(url=QDRANT_URL)
        
        # 1. Extraction des faits (Scroll)
        # On utilise try/except ici pour isoler la partie Qdrant
        try:
            all_points, _ = client.scroll(
                collection_name=COLLECTION_NAME, 
                limit=100, 
                with_payload=True
            )
        except Exception as q_err:
            return {"status": "Error", "message": f"Erreur Qdrant Scroll: {str(q_err)}"}

        real_titles = []
        for p in all_points:
            text = p.payload.get("text", "")
            for line in text.split('\n'):
                if line.startswith("TITRE:"):
                    title = line.replace("TITRE:", "").strip()
                    if "archiviste" not in title.lower():
                        real_titles.append(title)
        
        total_count = len(real_titles)
        titles_list = "\n".join([f"- {t}" for t in real_titles])

        # 2. Recherche par similarité
        # Si 'search' échoue, on peut utiliser 'query_points' sur les versions très récentes
        embeddings_model = get_embeddings()
        query_vector = embeddings_model.embed_query(query)
        
        try:
            search_result = client.search(
                collection_name=COLLECTION_NAME,
                query_vector=query_vector,
                limit=3
            )
            context_detail = "\n".join([res.payload["text"] for res in search_result])
        except AttributeError:
            # Fallback si 'search' n'est vraiment pas là (cas rare de versioning)
            context_detail = "Détails non disponibles via recherche vectorielle."

        # 3. Appel LLM
        llm = ChatOllama(model=MODEL_NAME, base_url=OLLAMA_BASE_URL)
        
        prompt = ChatPromptTemplate.from_template(
                """Tu es l'assistant précis du site. Tu dois analyser la liste des titres fournis pour répondre.

            DONNÉES RÉELLES :
            - Nombre total d'articles : {total}
            - Liste des titres : 
            {titles}

            CONSIGNES :
            1. Pour répondre à la question sur la langue, regarde chaque titre un par un.
            2. Ne fais JAMAIS d'estimation ou de pourcentage. Compte les titres manuellement.
            3. Si un titre est "Bonjour tout le monde !", il est en français.
            4. Si un titre ressemble à "Lorem ipsum" ou "Architecto earum", il est en latin.
            5. Sois direct : donne le chiffre exact et liste les titres correspondants.

            QUESTION : {question}
            RÉPONSE :"""
            )
        
        chain = prompt | llm | StrOutputParser()
        answer = chain.invoke({
            "total": total_count, 
            "titles": titles_list, 
            "context": context_detail, 
            "question": query
        })
        
        return {"answer": answer}

    except Exception as e:
        # On renvoie l'erreur précise pour le debug
        return {"status": "Error", "message": str(e)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)