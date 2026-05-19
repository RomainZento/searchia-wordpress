<?php
/**
 * Plugin Name: Mon Chatbot RAG Local
 * Description: Interface de chat stylisée connectée à l'API Python
 * Version: 1.1
 * Author: Ton Nom
 */

if (!defined('ABSPATH')) exit;

function mon_chatbot_enqueue_scripts() {
    ?>
    <style>
        :root {
            --chat-primary: #2271b1; /* Bleu WordPress */
            --chat-bg: #f0f2f5;
            --chat-text: #1d2327;
        }

        /* La Bulle Flottante */
        #ai-chat-launcher {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--chat-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            z-index: 10000;
        }
        #ai-chat-launcher:hover { transform: scale(1.1); }

        /* La Fenêtre de Chat */
        #ai-chat-window {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 10000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        /* Header */
        #ai-chat-header {
            background: var(--chat-primary);
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Zone des messages */
        #ai-chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: var(--chat-bg);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-msg {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        .msg-ai { background: white; align-self: flex-start; border-bottom-left-radius: 2px; color: var(--chat-text); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .msg-user { background: var(--chat-primary); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }

        /* Zone d'input */
        #ai-chat-input-area {
            padding: 15px;
            background: white;
            border-top: 1px solid #ddd;
            display: flex;
        }
        #ai-chat-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            outline: none;
        }
    </style>

    <div id="ai-chat-launcher">💬</div>

    <div id="ai-chat-window">
        <div id="ai-chat-header">
            <span>Assistant IA Local</span>
            <span style="cursor:pointer" onclick="document.getElementById('ai-chat-window').style.display='none'">✕</span>
        </div>
        <div id="ai-chat-messages">
            <div class="chat-msg msg-ai">Bonjour ! Je connais ce site par cœur. Posez-moi une question.</div>
        </div>
        <div id="ai-chat-input-area">
            <input type="text" id="ai-chat-input" placeholder="Posez votre question...">
        </div>
    </div>

    <script>
        const launcher = document.getElementById('ai-chat-launcher');
        const windowChat = document.getElementById('ai-chat-window');
        const input = document.getElementById('ai-chat-input');
        const messages = document.getElementById('ai-chat-messages');

        launcher.onclick = () => {
            windowChat.style.display = windowChat.style.display === 'flex' ? 'none' : 'flex';
        };

        input.onkeypress = async (e) => {
            if (e.key === 'Enter' && input.value.trim() !== '') {
                const query = input.value;
                input.value = '';

                // Ajout message utilisateur
                messages.innerHTML += `<div class="chat-msg msg-user">${query}</div>`;
                
                // Bulle de chargement
                const loading = document.createElement('div');
                loading.className = 'chat-msg msg-ai';
                loading.innerText = '...';
                messages.appendChild(loading);
                messages.scrollTop = messages.scrollHeight;

                try {
                    const response = await fetch(`http://localhost:8000/ask?query=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    loading.innerText = data.answer || "Désolé, je n'ai pas compris.";
                } catch (err) {
                    loading.innerText = "Erreur de connexion à l'IA.";
                }
                messages.scrollTop = messages.scrollHeight;
            }
        };
    </script>
    <?php
}
add_action('wp_footer', 'mon_chatbot_enqueue_scripts');
