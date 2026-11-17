import { Armchair } from 'lucide-react';

const TAKEN = ['reserved', 'booked', 'taken', 'occupied', 'sold', 'unavailable', 'blocked'];

export function normalizeSeats(raw) {
  const list = Array.isArray(raw) ? raw : raw?.seats ?? [];
  return list.map((s, i) => ({
    id: s.id ?? s.seat_id ?? i + 1,
    label: String(s.seat_number ?? s.number ?? s.label ?? s.name ?? i + 1),
    taken: s.is_available === false || s.available === false || s.is_reserved === true || TAKEN.includes(String(s.status || '').toLowerCase()),
  }));
}

export default function SeatMap({ seats, selected, onToggle }) {
  return (
    <div className="seatmap" role="group" aria-label="Plan des places du car">
      <div className="seatmap__front"><span>Chauffeur</span></div>
      <div className="seatmap__grid">
        {seats.map((s, i) => {
          const on = selected.includes(s.id);
          return (
            <div key={s.id} style={{ display: 'contents' }}>
              {i % 4 === 2 && <span className="aisle" />}
              <button
                type="button"
                disabled={s.taken}
                aria-pressed={on}
                aria-label={`Place ${s.label}${s.taken ? ', occupée' : ''}`}
                className={`seat ${s.taken ? 'is-taken' : ''} ${on ? 'is-on' : ''}`}
                onClick={() => onToggle(s.id)}
              >
                <Armchair size={18} />
                <small>{s.label}</small>
              </button>
            </div>
          );
        })}
      </div>
      <div className="seatmap__legend">
        <span><i className="seat-k free" /> Libre</span>
        <span><i className="seat-k sel" /> Votre choix</span>
        <span><i className="seat-k occ" /> Occupée</span>
      </div>
    </div>
  );
}
