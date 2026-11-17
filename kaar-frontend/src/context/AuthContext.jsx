import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '../api';
import { tokenStore } from '../api/client';

const AuthCtx = createContext(null);
export const useAuth = () => useContext(AuthCtx);

export const roleHome = (role) => (role === 'admin' ? '/admin' : role === 'company_admin' ? '/compagnie' : '/');

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(!!tokenStore.get());

  useEffect(() => {
    if (!tokenStore.get()) return;
    api.me().then((d) => setUser(d?.user ?? d)).catch(() => tokenStore.clear()).finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    const out = () => setUser(null);
    window.addEventListener('kaar:unauthorized', out);
    return () => window.removeEventListener('kaar:unauthorized', out);
  }, []);

  const finish = useCallback((d) => {
    tokenStore.set(d.token);
    setUser(d.user);
    return d.user;
  }, []);

  const value = useMemo(
    () => ({
      user,
      loading,
      login: async (b) => finish(await api.login(b)),
      register: async (b) => finish(await api.register(b)),
      logout: async () => {
        try { await api.logout(); } catch { /* jeton déjà invalide */ }
        tokenStore.clear();
        setUser(null);
      },
    }),
    [user, loading, finish]
  );
  return <AuthCtx.Provider value={value}>{children}</AuthCtx.Provider>;
}
