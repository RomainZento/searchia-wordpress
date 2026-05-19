'use client';

import { useState, useRef, useEffect, FormEvent } from 'react';

interface Message {
  id: string;
  role: 'user' | 'assistant';
  content: string;
}

interface ChatBotProps {
  apiUrl?: string;
  title?: string;
  placeholder?: string;
  welcomeMessage?: string;
  className?: string;
}

export default function ChatBot({
  apiUrl = 'http://localhost:8000',
  title = 'Assistant IA',
  placeholder = 'Posez votre question...',
  welcomeMessage = 'Bonjour ! Je connais ce site par cœur. Posez-moi une question.',
  className = '',
}: ChatBotProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>([
    { id: '0', role: 'assistant', content: welcomeMessage },
  ]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Auto-scroll vers le bas
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const sendMessage = async (e: FormEvent) => {
    e.preventDefault();
    if (!input.trim() || isLoading) return;

    const userMessage: Message = {
      id: Date.now().toString(),
      role: 'user',
      content: input.trim(),
    };

    setMessages((prev) => [...prev, userMessage]);
    setInput('');
    setIsLoading(true);

    try {
      const response = await fetch(
        `${apiUrl}/ask?query=${encodeURIComponent(userMessage.content)}`
      );
      const data = await response.json();

      const assistantMessage: Message = {
        id: (Date.now() + 1).toString(),
        role: 'assistant',
        content: data.answer || data.message || "Désolé, je n'ai pas compris.",
      };

      setMessages((prev) => [...prev, assistantMessage]);
    } catch (error) {
      setMessages((prev) => [
        ...prev,
        {
          id: (Date.now() + 1).toString(),
          role: 'assistant',
          content: "Erreur de connexion à l'IA. Vérifiez que le serveur est lancé.",
        },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      {/* Bouton flottant */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className={`fixed bottom-6 right-6 w-14 h-14 bg-blue-600 hover:bg-blue-700 
          text-white rounded-full shadow-lg flex items-center justify-center 
          text-2xl transition-transform hover:scale-110 z-50 ${className}`}
        aria-label="Ouvrir le chat"
      >
        {isOpen ? '✕' : '💬'}
      </button>

      {/* Fenêtre de chat */}
      {isOpen && (
        <div
          className="fixed bottom-24 right-6 w-80 sm:w-96 h-[500px] bg-white 
            rounded-2xl shadow-2xl flex flex-col overflow-hidden z-50 
            border border-gray-200"
        >
          {/* Header */}
          <div className="bg-blue-600 text-white px-4 py-3 font-semibold flex justify-between items-center">
            <span>{title}</span>
            <button
              onClick={() => setIsOpen(false)}
              className="hover:bg-blue-700 rounded p-1 transition-colors"
            >
              ✕
            </button>
          </div>

          {/* Messages */}
          <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            {messages.map((msg) => (
              <div
                key={msg.id}
                className={`max-w-[80%] p-3 rounded-2xl text-sm leading-relaxed ${
                  msg.role === 'user'
                    ? 'bg-blue-600 text-white ml-auto rounded-br-sm'
                    : 'bg-white text-gray-800 shadow-sm rounded-bl-sm'
                }`}
              >
                {msg.content}
              </div>
            ))}
            {isLoading && (
              <div className="bg-white text-gray-500 p-3 rounded-2xl rounded-bl-sm shadow-sm max-w-[80%] text-sm">
                <span className="animate-pulse">...</span>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Input */}
          <form onSubmit={sendMessage} className="p-3 border-t bg-white">
            <div className="flex gap-2">
              <input
                type="text"
                value={input}
                onChange={(e) => setInput(e.target.value)}
                placeholder={placeholder}
                disabled={isLoading}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-full 
                  focus:outline-none focus:ring-2 focus:ring-blue-500 
                  disabled:bg-gray-100 text-sm"
              />
              <button
                type="submit"
                disabled={isLoading || !input.trim()}
                className="px-4 py-2 bg-blue-600 text-white rounded-full 
                  hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed 
                  transition-colors text-sm font-medium"
              >
                ↑
              </button>
            </div>
          </form>
        </div>
      )}
    </>
  );
}
