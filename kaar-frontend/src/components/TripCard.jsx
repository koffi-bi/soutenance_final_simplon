import { Link } from 'react-router-dom';
import { Armchair, Clock } from 'lucide-react';
import { useUI } from '../context/UIContext';
import { fcfa, fmtDate, fmtDuration, fmtTime } from '../lib/format';

export default function TripCard({ trip: t, pax = 1 }) {
  const { openBooking } = useUI();
  const low = t.available_seats > 0 && t.available_seats <= 5;
  return (
    <article className="trip">
      <div className="trip__co">
        {t.company_logo ? <img src={t.company_logo} alt="" /> : <span className="trip__logo">{t.company_name?.[0]}</span>}
        <div><b>{t.company_name}</b><small>{t.vehicle_type || t.vehicle_label}</small></div>
      </div>
      <div className="trip__time">
        <div><b>{fmtTime(t.departure_at)}</b><small>{t.departure_city}</small></div>
        <div className="trip__mid"><Clock size={14} />{fmtDuration(t.estimated_duration_minutes)}</div>
        <div><b>{fmtTime(t.arrival_at)}</b><small>{t.arrival_city}</small></div>
      </div>
      <div className="trip__meta">
        <span>{fmtDate(t.departure_at)}</span>
        <span className={low ? 'warn' : ''}><Armchair size={14} /> {t.available_seats} place{t.available_seats > 1 ? 's' : ''}</span>
      </div>
      <div className="trip__buy">
        <p><b>{fcfa(t.price)}</b> <small>FCFA</small></p>
        <div>
          <Link className="btn btn-ghost btn-sm" to={`/trajets/${t.id}`}>Détails</Link>
          <button className="btn btn-primary btn-sm" disabled={t.available_seats < 1} onClick={() => openBooking(t)}>
            {t.available_seats < 1 ? 'Complet' : 'Réserver'}
          </button>
        </div>
      </div>
    </article>
  );
}
