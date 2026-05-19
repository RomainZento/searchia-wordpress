# Intégration Next.js 16

Ce dossier contient un exemple de composant React pour intégrer le chatbot RAG dans une application Next.js.

## Installation

Copiez le composant `ChatBot.tsx` dans votre projet Next.js :

```bash
cp ChatBot.tsx votre-projet-nextjs/components/
```

## Utilisation

```tsx
import ChatBot from '@/components/ChatBot';

export default function Page() {
  return (
    <div>
      <h1>Mon site</h1>
      <ChatBot />
    </div>
  );
}
```

## Configuration

Par défaut, le composant se connecte à `http://localhost:8000`. Pour changer l'URL de l'API :

```tsx
<ChatBot apiUrl="https://votre-api.com" />
```

## Personnalisation

Le composant utilise Tailwind CSS. Vous pouvez personnaliser les styles en modifiant les classes.

### Version sans UI (hook uniquement)

Si vous voulez créer votre propre interface, utilisez le hook `useChatBot.ts` :

```tsx
import { useChatBot } from '@/hooks/useChatBot';

export default function MyCustomChat() {
  const { messages, sendMessage, isLoading } = useChatBot();

  return (
    // Votre interface custom
  );
}
```
