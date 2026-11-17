import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Armchair, Bus, Clock, Loader2 } from 'lucide-react';
import { api } from '../api';
import { useUI } from '../context/UIContext';
import { fcfa, fmtDate, fmtDuration, fmtTime } from '../lib/format';

export default function TripDetail() {
  const { id } = useParams();
  const { openBooking } = useUI();
  const [t, setT] = useState(null);
  const [err, setErr] = useState('');
  useEffect(() => { api.trip(id).then(setT).catch((e) => setErr(e.message)); }, [id]);

  if (err) return <div className="page container"><p className="alert alert--error">{err}</p><Link to="/trajets">Retour aux voyages</Link></div>;
  if (!t) return <div className="page container center"><Loader2 className="spin" /></div>;

  return (
    <div className="page container detail">
      <Link to="/trajets" className="back">← Tous les voyages</Link>
      <div className="detail__card">
        <p className="muted">{t.company_name}</p>
        <h1>{t.departure_city} <span>→</span> {t.arrival_city}</h1>
        <div className="detail__times">
          <div><b>{fmtTime(t.departure_at)}</b><small>{fmtDate(t.departure_at)}</small></div>
          <div className="trip__mid"><Clock size={14} /> {fmtDuration(t.estimated_duration_minutes)}</div>
          <div><b>{fmtTime(t.arrival_at)}</b><small>{fmtDate(t.arrival_at)}</small></div>
        </div>
        <ul className="detail__facts">
          <li><Bus size={18} /> {t.vehicle_label} {t.vehicle_type && `· ${t.vehicle_type}`}</li>
          <li><Armchair size={18} /> {t.available_seats} place{t.available_seats > 1 ? 's' : ''} disponible{t.available_seats > 1 ? 's' : ''}</li>
        </ul>
        <div className="detail__buy">
          <p><b>{fcfa(t.price)}</b> <small>FCFA / place</small></p>
          <button className="btn btn-primary" disabled={t.available_seats < 1} onClick={() => openBooking(t)}>Réserver ce voyage</button>
        </div>
      </div>
    </div>
  );
}
