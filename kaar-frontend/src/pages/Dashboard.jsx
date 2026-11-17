import { useEffect, useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Building2, Bus, CreditCard, Home, LayoutDashboard, Loader2, LogOut, Ticket, Users, Wallet, CalendarCheck } from 'lucide-react';
import Logo from '../components/Logo';
import { api } from '../api';
import { useAuth } from '../context/AuthContext';
import { fcfa } from '../lib/format';

const C = { orange: '#FF6B1F', navy: '#0D2748', blue: '#3B7DDD', green: '#1FA67A', amber: '#F5B63D', red: '#E5484D', grey: '#A8B0BD', violet: '#7A6CF0' };

const F = {
  total: ['Total', C.navy], scheduled: ['Programmés', C.blue], confirmed: ['Confirmées', C.green], pending: ['En attente', C.amber],
  cancelled: ['Annulées', C.red], expired: ['Expirées', C.grey], paid: ['Réussis', C.green], failed: ['Échoués', C.red],
  refunded: ['Remboursés', C.violet], valid: ['Valides', C.green], used: ['Utilisés', C.blue], admins: ['Administrateurs', C.navy],
  company_admins: ['Admins de compagnie', C.orange], travelers: ['Voyageurs', C.blue], active: ['Actives', C.green],
};

const SECTIONS = {
  admin: [
    { key: 'users', label: 'Utilisateurs', icon: Users, tint: 'blue', fields: ['travelers', 'company_admins', 'admins'], chart: 'donut' },
    { key: 'companies', label: 'Compagnies', icon: Building2, tint: 'orange', fields: ['active', 'total'], chart: 'bar' },
    { key: 'trips', label: 'Voyages', icon: Bus, tint: 'green', fields: ['scheduled', 'total'], chart: 'bar' },
    { key: 'reservations', label: 'Réservations', icon: CalendarCheck, tint: 'violet', fields: ['confirmed', 'pending', 'cancelled', 'expired'], chart: 'bar' },
    { key: 'payments', label: 'Paiements', icon: Wallet, tint: 'amber', fields: ['paid', 'pending', 'failed', 'refunded'], chart: 'donut', revenue: true },
    { key: 'tickets', label: 'Billets', icon: Ticket, tint: 'orange', fields: ['valid', 'used'], chart: 'donut' },
  ],
  company: [
    { key: 'trips', label: 'Voyages', icon: Bus, tint: 'green', fields: ['scheduled', 'total'], chart: 'bar' },
    { key: 'reservations', label: 'Réservations', icon: CalendarCheck, tint: 'violet', fields: ['confirmed', 'pending', 'cancelled', 'expired'], chart: 'bar' },
    { key: 'travelers', label: 'Voyageurs', icon: Users, tint: 'blue', fields: ['total'], chart: null },
    { key: 'payments', label: 'Paiements', icon: Wallet, tint: 'amber', fields: ['paid', 'pending', 'failed', 'refunded'], chart: 'donut', revenue: true },
    { key: 'tickets', label: 'Billets', icon: Ticket, tint: 'orange', fields: ['valid', 'used'], chart: 'donut' },
  ],
};

const n = (d, k, f) => Number(d?.[k]?.[f] ?? 0);
const series = (d, s) => s.fields.filter((f) => f !== 'total' || s.fields.length === 1 || s.chart === 'bar').map((f) => ({ name: F[f][0], value: n(d, s.key, f), color: F[f][1] }));

function Stat({ icon: Icon, tint, value, label }) {
  return <div className="stat"><span className={`ic ic--${tint}`}><Icon size={20} /></span><div><b>{value}</b><small>{label}</small></div></div>;
}

function Donut({ data, center, sub }) {
  const sum = data.reduce((a, x) => a + x.value, 0);
  return (
    <div className="donut">
      <ResponsiveContainer width="100%" height={210}>
        <PieChart>
          <Pie data={sum ? data : [{ name: '—', value: 1, color: '#EEF0F4' }]} dataKey="value" innerRadius={62} outerRadius={86} paddingAngle={sum ? 3 : 0} cornerRadius={8} stroke="none">
            {(sum ? data : [{ color: '#EEF0F4' }]).map((x, i) => <Cell key={i} fill={x.color} />)}
          </Pie>
          {sum > 0 && <Tooltip />}
        </PieChart>
      </ResponsiveContainer>
      <div className="donut__c"><b>{center}</b><small>{sub}</small></div>
      <ul className="legend">{data.map((x) => <li key={x.name}><i style={{ background: x.color }} />{x.name} <b>{x.value}</b></li>)}</ul>
    </div>
  );
}

function Bars({ data }) {
  return (
    <ResponsiveContainer width="100%" height={250}>
      <BarChart data={data} margin={{ top: 10, right: 8, left: -18, bottom: 0 }}>
        <CartesianGrid vertical={false} stroke="#EEF0F4" />
        <XAxis dataKey="name" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#6B7688' }} />
        <YAxis allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#6B7688' }} />
        <Tooltip cursor={{ fill: 'rgba(255,107,31,.06)' }} />
        <Bar dataKey="value" radius={[8, 8, 0, 0]} maxBarSize={44}>{data.map((x, i) => <Cell key={i} fill={x.color} />)}</Bar>
      </BarChart>
    </ResponsiveContainer>
  );
}

const Chart = ({ s, d }) => {
  const data = series(d, s);
  if (s.chart === 'donut') {
    const total = n(d, s.key, 'total') || data.reduce((a, x) => a + x.value, 0);
    const first = data[0]?.value || 0;
    return <Donut data={data} center={s.key === 'payments' || s.key === 'tickets' ? `${total ? Math.round((first / total) * 100) : 0}%` : total} sub={s.key === 'payments' ? 'réussis' : s.key === 'tickets' ? 'valides' : 'au total'} />;
  }
  return s.chart === 'bar' ? <Bars data={data} /> : null;
};

export default function Dashboard({ scope }) {
  const { user, loading, logout } = useAuth();
  const nav = useNavigate();
  const role = scope === 'admin' ? 'admin' : 'company_admin';
  const [d, setD] = useState(null);
  const [err, setErr] = useState('');
  const [tab, setTab] = useState('overview');
  const sections = SECTIONS[scope];

  useEffect(() => {
    if (user?.role !== role) return;
    (scope === 'admin' ? api.adminDashboard() : api.companyDashboard()).then(setD).catch((e) => setErr(e.message));
  }, [user, role, scope]);

  if (loading) return <div className="center" style={{ padding: 80 }}><Loader2 className="spin" /></div>;
  if (user?.role !== role) return <Navigate to="/" replace />;

  const rev = fcfa(n(d, 'payments', 'revenue'));
  const top = scope === 'admin'
    ? [['users', 'Utilisateurs', Users, 'blue', n(d, 'users', 'total')], ['companies', 'Compagnies', Building2, 'orange', n(d, 'companies', 'total')], ['trips', 'Voyages', Bus, 'green', n(d, 'trips', 'total')], ['rev', 'Revenus (FCFA)', Wallet, 'amber', rev]]
    : [['trips', 'Voyages programmés', Bus, 'green', n(d, 'trips', 'scheduled')], ['res', 'Réservations', CalendarCheck, 'violet', n(d, 'reservations', 'total')], ['trav', 'Voyageurs', Users, 'blue', n(d, 'travelers', 'total')], ['rev', 'Revenus (FCFA)', Wallet, 'amber', rev]];
  const cur = sections.find((s) => s.key === tab);
  const resSec = sections.find((s) => s.key === 'reservations');
  const paySec = sections.find((s) => s.key === 'payments');

  return (
    <div className="dash">
      <aside className="dash__side">
        <Logo />
        <p className="dash__who">{scope === 'admin' ? 'Administration' : 'Espace compagnie'}</p>
        <nav>
          <button className={tab === 'overview' ? 'on' : ''} onClick={() => setTab('overview')}><LayoutDashboard size={18} /> Vue d'ensemble</button>
          {sections.map((s) => <button key={s.key} className={tab === s.key ? 'on' : ''} onClick={() => setTab(s.key)}><s.icon size={18} /> {s.label}</button>)}
        </nav>
        <div className="dash__foot">
          <Link to="/"><Home size={16} /> Retour au site</Link>
          <button onClick={async () => { await logout(); nav('/'); }}><LogOut size={16} /> Déconnexion</button>
        </div>
      </aside>

      <main className="dash__main">
        <header className="dash__head">
          <div><h1>{tab === 'overview' ? 'Tableau de bord' : cur.label}</h1>
            <p className="muted">Bonjour {user.first_name}, voici l'activité {scope === 'admin' ? 'de toute la plateforme' : 'de votre compagnie'}.</p></div>
          <span className="avatar">{`${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`}</span>
        </header>
        {err && <p className="alert alert--error">{err}</p>}
        {!d && !err && <p className="center"><Loader2 className="spin" /></p>}

        {d && tab === 'overview' && (
          <>
            <div className="stats">{top.map(([k, l, I, t, v]) => <Stat key={k} icon={I} tint={t} value={v} label={l} />)}</div>
            <div className="dash__row">
              <section className="card"><h3>Réservations par statut</h3><Bars data={series(d, resSec)} /></section>
              <section className="card"><h3>Paiements</h3><Chart s={paySec} d={d} /></section>
            </div>
            <div className="dash__row dash__row--3">
              {sections.filter((s) => s.chart === 'donut' && s.key !== 'payments').map((s) => (
                <section className="card" key={s.key}><h3>{s.label}</h3><Chart s={s} d={d} /></section>
              ))}
            </div>
          </>
        )}

        {d && cur && (
          <>
            <div className="stats">
              {cur.fields.map((f) => <Stat key={f} icon={cur.icon} tint={cur.tint} value={n(d, cur.key, f)} label={F[f][0]} />)}
              {cur.revenue && <Stat icon={CreditCard} tint="green" value={`${rev} FCFA`} label="Revenus" />}
            </div>
            {cur.chart && <section className="card"><h3>Répartition</h3><Chart s={cur} d={d} /></section>}
          </>
        )}
      </main>
    </div>
  );
}
