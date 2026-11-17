const BASE = (import.meta.env.VITE_API_URL || 'http://localhost:8080').replace(/\/$/, '');
const KEY = 'kaar_token';

export const tokenStore = {
  get: () => localStorage.getItem(KEY),
  set: (t) => localStorage.setItem(KEY, t),
  clear: () => localStorage.removeItem(KEY),
};

export class ApiError extends Error {
  constructor(message, status, errors) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

const firstError = (errors) => {
  if (!errors || typeof errors !== 'object') return null;
  const v = Object.values(errors)[0];
  return Array.isArray(v) ? v[0] : typeof v === 'string' ? v : null;
};

export async function request(path, { method = 'GET', body, params, blob } = {}) {
  const url = new URL(BASE + path);
  if (params) Object.entries(params).forEach(([k, v]) => v !== '' && v != null && url.searchParams.set(k, v));
  const headers = { Accept: blob ? 'application/pdf' : 'application/json' };
  const token = tokenStore.get();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body) headers['Content-Type'] = 'application/json';

  let res;
  try {
    res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined });
  } catch {
    throw new ApiError('Impossible de joindre le serveur. Vérifiez votre connexion.', 0);
  }
  if (blob && res.ok) return res.blob();

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401 && token) {
      tokenStore.clear();
      window.dispatchEvent(new Event('kaar:unauthorized'));
    }
    throw new ApiError(firstError(json.errors) || json.message || 'Une erreur est survenue.', res.status, json.errors);
  }
  return json;
}
