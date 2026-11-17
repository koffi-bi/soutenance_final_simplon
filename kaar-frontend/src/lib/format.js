export const fcfa = (n) =>
  new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0)).replace(/[\u202f\u00a0]/g, ' ');

const toDate = (v) => (v ? new Date(String(v).replace(' ', 'T')) : null);

export const fmtTime = (v) => {
  const d = toDate(v);
  return d && !isNaN(d) ? d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
};
export const fmtDate = (v, opts = { weekday: 'short', day: 'numeric', month: 'long' }) => {
  const d = toDate(v);
  return d && !isNaN(d) ? d.toLocaleDateString('fr-FR', opts) : '';
};
export const fmtDuration = (min) => {
  const m = Number(min) || 0;
  const h = Math.floor(m / 60);
  const r = m % 60;
  return h ? `${h}h ${String(r).padStart(2, '0')}` : `${r} min`;
};
export const asList = (d) => (Array.isArray(d) ? d : d?.items ?? d?.tickets ?? d?.reservations ?? d?.data ?? []);
export const todayISO = () => new Date().toISOString().slice(0, 10);
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
