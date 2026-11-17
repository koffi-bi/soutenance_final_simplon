import { useNavigate } from 'react-router-dom';
import Modal from './Modal';
import AuthForm from './AuthForm';
import { useUI } from '../context/UIContext';
import { roleHome } from '../context/AuthContext';

export default function AuthModal() {
  const { authMode, closeAuth } = useUI();
  const nav = useNavigate();
  if (!authMode) return null;
  return (
    <Modal onClose={closeAuth} label="Connexion ou inscription">
      <h2 className="modal__title">Bienvenue sur Kaar</h2>
      <AuthForm
        initialMode={authMode}
        onSuccess={(u) => { closeAuth(); const h = roleHome(u.role); if (h !== '/') nav(h); }}
      />
    </Modal>
  );
}
