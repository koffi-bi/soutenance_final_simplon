import { useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Download, Loader2 } from 'lucide-react';
import { LogoMark } from './Logo';
import { api } from '../api';
import { fmtDate, fmtTime, fmtDuration } from '../lib/format';

// Les champs du billet ne sont pas détaillés dans openapi.yaml : chaque valeur a un repli.
const pick = (...v) => v.find((x) => x !== undefined && x !== null && x !== '');

export function normalizeTicket(t, trip = {}, user) {
  return {
    id: t.id,
    code: pick(t.ticket_number, t.ticket_code, t.code, t.reference, t.qr_code, t.id && `KAAR-${String(t.id).padStart(6, '0')}`),
    status: pick(t.status, 'valid'),
    passenger: pick(t.passenger_name, t.traveler_name, t.user_name, user && `${user.first_name} ${user.last_name}`, 'Voyageur'),
    seat: pick(t.seat_number, t.seat_label, t.seat, '—'),
    company: pick(t.company_name, trip.company_name, 'Kaar'),
    from: pick(t.departure_city, trip.departure_city, '—'),
    to: pick(t.arrival_city, trip.arrival_city, '—'),
    depAt: pick(t.departure_at, trip.departure_at),
    arrAt: pick(t.arrival_at, trip.arrival_at),
    vehicle: pick(t.vehicle_label, trip.vehicle_label, ''),
    price: pick(t.price, t.amount, trip.price),
    duration: pick(t.estimated_duration_minutes, trip.estimated_duration_minutes),
  };
}

const STATUS = { valid: 'Valide', used: 'Utilisé', cancelled: 'Annulé', expired: 'Expiré' };

export default function TicketCard({ ticket, trip, user }) {
  const t = normalizeTicket(ticket, trip, user);
  const [busy, setBusy] = useState(false);

  const download = async () => {
    setBusy(true);
    try {
      const blob = await api.ticketPdf(t.id);
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `billet-kaar-${t.code}.pdf`;
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) { alert(e.message); } finally { setBusy(false); }
  };

  return (
    <article className="ticket">
      <div className="ticket__main">
        <header className="ticket__top">
          <span className="ticket__brand"><LogoMark size={26} /> Kaar<i>.</i></span>
          <span className="ticket__co">{t.company}</span>
          <span className={`ticket__st st--${t.status}`}>{STATUS[t.status] || t.status}</span>
        </header>

        <div className="ticket__route">
          <div><small>Départ</small><b>{t.from}</b><em>{fmtTime(t.depAt)}</em></div>
          <div className="ticket__line"><span>{t.duration ? fmtDuration(t.duration) : ''}</span></div>
          <div className="ticket__r"><small>Arrivée</small><b>{t.to}</b><em>{fmtTime(t.arrAt)}</em></div>
        </div>

        <dl className="ticket__grid">
          <div><dt>Passager</dt><dd>{t.passenger}</dd></div>
          <div><dt>Date</dt><dd>{fmtDate(t.depAt, { day: 'numeric', month: 'short', year: 'numeric' })}</dd></div>
          <div><dt>Place</dt><dd className="seatno">{t.seat}</dd></div>
          {t.vehicle && <div><dt>Car</dt><dd>{t.vehicle}</dd></div>}
        </dl>
      </div>

      <div className="ticket__stub">
        <QRCodeSVG value={`KAAR:${t.code}`} size={104} fgColor="#071B37" bgColor="transparent" level="M" />
        <code>{t.code}</code>
        {t.id && (
          <button className="btn btn-dark btn-sm" onClick={download} disabled={busy}>
            {busy ? <Loader2 className="spin" size={15} /> : <Download size={15} />} PDF
          </button>
        )}
      </div>
    </article>
  );
}
