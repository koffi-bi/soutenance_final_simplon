import { useEffect, useRef, useState } from 'react';
import { Bot, Send, X, Loader2 } from 'lucide-react';
import { api } from '../api';
import { useUI } from '../context/UIContext';
import { useAuth } from '../context/AuthContext';

const GREETING = 'Bonjour ! Je suis Kôkô. Je peux vous renseigner sur les trajets, les réservations et les places disponibles.';
const CHIPS = ['Réservations aujourd\'hui', 'Places disponibles', 'Prochains trajets'];

export default function ChatWidget() {
  const { chatOpen, setChatOpen, messages, setMessages, openAuth } = useUI();
  const { user } = useAuth();
  const [text, setText] = useState('');
  const [busy, setBusy] = useState(false);
  const end = useRef(null);

  useEffect(() => { end.current?.scrollIntoView({ behavior: 'smooth' }); }, [messages, busy, chatOpen]);

  const send = async (raw) => {
    const message = (raw ?? text).trim();
    if (!message || busy) return;
    setText('');
    setMessages((m) => [...m, { from: 'me', text: message }]);
    setBusy(true);
    try {
      const r = await api.chat(message);
      setMessages((m) => [...m, { from: 'bot', text: r.message }]);
    } catch (e) {
      const auth = e.status === 401;
      setMessages((m) => [...m, { from: 'bot', text: auth ? 'Connectez-vous pour discuter avec Kôkô.' : e.status === 502 ? 'Kôkô est momentanément indisponible. Réessayez dans un instant.' : e.message, auth }]);
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      {!chatOpen && (
        <button className="fab" onClick={() => setChatOpen(true)} aria-label="Ouvrir le chat avec Kôkô">
          <span className="fab__ic"><Bot size={22} /></span>
          <span><b>Besoin d'aide ?</b><small>Discutez avec Kôkô</small></span>
        </button>
      )}
      {chatOpen && (
        <section className="chat" aria-label="Chat avec Kôkô">
          <header className="chat__head">
            <span className="chat__av"><Bot size={20} /></span>
            <div><b>Kôkô</b><small>Assistant de voyage</small></div>
            <button onClick={() => setChatOpen(false)} aria-label="Fermer le chat"><X size={18} /></button>
          </header>
          <div className="chat__body" aria-live="polite">
            <div className="bubble bubble--bot">{GREETING}</div>
            {!messages.length && CHIPS.map((c) => (
              <button key={c} className="chip" onClick={() => send(c)}>{c}</button>
            ))}
            {messages.map((m, i) => (
              <div key={i} className={`bubble bubble--${m.from}`}>
                {m.text}
                {m.auth && !user && <button className="bubble__link" onClick={() => openAuth('login')}>Se connecter</button>}
              </div>
            ))}
            {busy && <div className="bubble bubble--bot"><Loader2 size={16} className="spin" /> Kôkô écrit…</div>}
            <div ref={end} />
          </div>
          <form className="chat__form" onSubmit={(e) => { e.preventDefault(); send(); }}>
            <input value={text} onChange={(e) => setText(e.target.value)} maxLength={2000} placeholder="Posez votre question…" aria-label="Votre message" />
            <button className="chat__send" disabled={busy || !text.trim()} aria-label="Envoyer"><Send size={18} /></button>
          </form>
        </section>
      )}
    </>
  );
}
