import { useEffect, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import TicketCard from '../components/TicketCard';
import { api } from '../api';
import { useAuth } from '../context/AuthContext';
import { asList, fcfa } from '../lib/format';

const RES = { confirmed: 'Confirmée', pending: 'En attente', cancelled: 'Annulée', expired: 'Expirée' };

export default function MyTickets() {
  const { user, loading } = useAuth();
  const [tab, setTab] = useState('tickets');
  const [tickets, setTickets] = useState(null);
  const [res, setRes] = useState(null);

  useEffect(() => {
    if (!user) return;
    api.tickets().then((d) => setTickets(asList(d))).catch(() => setTickets([]));
    api.reservations().then((d) => setRes(asList(d))).catch(() => setRes([]));
  }, [user]);

  if (loading) return <div className="page container center"><Loader2 className="spin" /></div>;
  if (!user) return <Navigate to="/" replace />;

  return (
    <div className="page container">
      <h1 className="page__title">Mes voyages</h1>
      <div className="tabs tabs--dir">
        <button className={tab === 'tickets' ? 'on' : ''} onClick={() => setTab('tickets')}>Billets</button>
        <button className={tab === 'res' ? 'on' : ''} onClick={() => setTab('res')}>Réservations</button>
      </div>
      {tab === 'tickets' && (tickets === null ? <p className="center"><Loader2 className="spin" /></p> : tickets.length
        ? <div className="stack">{tickets.map((t) => <TicketCard key={t.id} ticket={t} user={user} />)}</div>
        : <div className="empty"><b>Aucun billet pour l'instant</b><p>Vos billets apparaissent ici après le paiement.</p><Link className="btn btn-primary" to="/trajets">Trouver un voyage</Link></div>)}
      {tab === 'res' && (res === null ? <p className="center"><Loader2 className="spin" /></p> : res.length
        ? <div className="table"><table><thead><tr><th>Référence</th><th>Trajet</th><th>Places</th><th>Montant</th><th>Statut</th></tr></thead>
          <tbody>{res.map((r) => (
            <tr key={r.id}><td>{r.reference}</td><td>{r.departure_city && `${r.departure_city} → ${r.arrival_city}`}</td><td>{r.number_of_seats}</td><td>{fcfa(r.total_amount)} FCFA</td><td><span className={`badge b--${r.status}`}>{RES[r.status] || r.status}</span></td></tr>
          ))}</tbody></table></div>
        : <div className="empty"><b>Aucune réservation</b></div>)}
    </div>
  );
}
