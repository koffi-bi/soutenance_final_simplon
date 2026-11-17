import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

export default function AuthForm({ initialMode = 'login', onSuccess, intro }) {
  const { login, register } = useAuth();
  const [mode, setMode] = useState(initialMode);
  const [f, setF] = useState({ first_name: '', last_name: '', email: '', phone: '', password: '', password_confirm: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const set = (k) => (e) => setF({ ...f, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    setError('');
    if (mode === 'register' && f.password !== f.password_confirm) return setError('Les mots de passe ne correspondent pas.');
    setBusy(true);
    try {
      const user = mode === 'login'
        ? await login({ email: f.email, password: f.password })
        : await register(f);
      onSuccess?.(user);
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <form className="auth" onSubmit={submit}>
      <div className="tabs" role="tablist">
        <button type="button" role="tab" aria-selected={mode === 'login'} className={mode === 'login' ? 'on' : ''} onClick={() => setMode('login')}>Se connecter</button>
        <button type="button" role="tab" aria-selected={mode === 'register'} className={mode === 'register' ? 'on' : ''} onClick={() => setMode('register')}>Créer un compte</button>
      </div>
      {intro && <p className="auth__intro">{intro}</p>}
      {mode === 'register' && (
        <div className="grid2">
          <label className="field"><span>Prénom</span><input required value={f.first_name} onChange={set('first_name')} autoComplete="given-name" /></label>
          <label className="field"><span>Nom</span><input required value={f.last_name} onChange={set('last_name')} autoComplete="family-name" /></label>
        </div>
      )}
      <label className="field"><span>Adresse e-mail</span><input type="email" required value={f.email} onChange={set('email')} autoComplete="email" placeholder="vous@exemple.com" /></label>
      {mode === 'register' && (
        <label className="field"><span>Téléphone</span><input type="tel" value={f.phone} onChange={set('phone')} placeholder="07 00 00 00 00" autoComplete="tel" /></label>
      )}
      <label className="field"><span>Mot de passe</span><input type="password" required minLength={mode === 'register' ? 8 : undefined} value={f.password} onChange={set('password')} autoComplete={mode === 'login' ? 'current-password' : 'new-password'} /></label>
      {mode === 'register' && (
        <label className="field"><span>Confirmer le mot de passe</span><input type="password" required value={f.password_confirm} onChange={set('password_confirm')} autoComplete="new-password" /></label>
      )}
      {error && <p className="alert alert--error" role="alert">{error}</p>}
      <button className="btn btn-primary btn-block" disabled={busy}>
        {busy && <Loader2 className="spin" size={18} />}
        {mode === 'login' ? 'Se connecter' : 'Créer mon compte'}
      </button>
    </form>
  );
}
