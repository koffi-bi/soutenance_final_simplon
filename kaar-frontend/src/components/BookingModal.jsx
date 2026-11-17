import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { AlertCircle, Banknote, CheckCircle2, CreditCard, Loader2, Smartphone } from 'lucide-react';
import Modal from './Modal';
import AuthForm from './AuthForm';
import SeatMap, { normalizeSeats } from './SeatMap';
import TicketCard from './TicketCard';
import { api } from '../api';
import { useAuth } from '../context/AuthContext';
import { useUI } from '../context/UIContext';
import { asList, fcfa, fmtDate, fmtTime, sleep } from '../lib/format';

const OPERATORS = [
  { id: 'orange', name: 'Orange Money', color: '#FF7900' },
  { id: 'mtn', name: 'MTN MoMo', color: '#F5C400', dark: true },
  { id: 'moov', name: 'Moov Money', color: '#0A6CC4' },
  { id: 'wave', name: 'Wave', color: '#1DC8F2', dark: true },
];
const STEPS = ['Places', 'Paiement', 'Billet'];

export default function BookingModal() {
  const { bookingTrip: trip, closeBooking } = useUI();
  const { user, logout } = useAuth();
  const nav = useNavigate();

  const [step, setStep] = useState(0);
  const [seats, setSeats] = useState([]);
  const [loadingSeats, setLoadingSeats] = useState(true);
  const [selected, setSelected] = useState([]);
  const [reservation, setReservation] = useState(null);
  const [deadline, setDeadline] = useState(0);
  const [left, setLeft] = useState(0);
  const [method, setMethod] = useState('mobile_money');
  const [operator, setOperator] = useState('orange');
  const [phone, setPhone] = useState('');
  const [card, setCard] = useState({ number: '', exp: '', cvc: '' });
  const [phase, setPhase] = useState('form'); // form | processing | failed
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [tickets, setTickets] = useState([]);

  const isTraveler = user && user.role === 'traveler';
  const total = reservation?.total_amount ?? selected.length * Number(trip.price);

  useEffect(() => {
    let off = false;
    (async () => {
      let raw;
      try { raw = await api.tripSeats(trip.id); } catch {
        try { raw = await api.trip(trip.id); } catch { raw = null; }
      }
      let list = normalizeSeats(raw);
      // Repli visuel si l'API ne fournit pas le plan de places (voir README)
      if (!list.length) list = Array.from({ length: Math.max(trip.available_seats, 0) }, (_, i) => ({ id: i + 1, label: String(i + 1), taken: false, fallback: true }));
      if (!off) { setSeats(list); setLoadingSeats(false); }
    })();
    return () => { off = true; };
  }, [trip.id, trip.available_seats]);

  useEffect(() => {
    if (!deadline || step !== 1) return;
    const t = setInterval(() => setLeft(Math.max(0, Math.round((deadline - Date.now()) / 1000))), 1000);
    return () => clearInterval(t);
  }, [deadline, step]);

  const toggle = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

  const reserve = async () => {
    setBusy(true); setError('');
    try {
      const r = await api.createReservation({ trip_id: trip.id, seat_ids: selected });
      setReservation(r);
      const ms = (r.expires_in_minutes ?? 15) * 60000;
      setDeadline(Date.now() + ms); setLeft(Math.round(ms / 1000));
      setStep(1);
    } catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  const pay = async (success = true) => {
    setError(''); setPhase('processing');
    try {
      const p = await api.createPayment({ reservation_id: reservation.reservation_id, payment_mode: 'simulation', payment_method: method });
      const paymentId = p?.id ?? p?.payment_id ?? p?.payment?.id;
      await sleep(2800); // simulation de la validation sur le téléphone
      await api.simulatePayment({ payment_id: paymentId, success });
      if (!success) { setPhase('failed'); return; }
      try {
        const all = asList(await api.tickets());
        const mine = all.filter((t) => String(t.reservation_id) === String(reservation.reservation_id));
        setTickets(mine.length ? mine : all.slice(-selected.length));
      } catch { setTickets([]); }
      setStep(2); setPhase('form');
    } catch (e) { setError(e.message); setPhase('form'); }
  };

  const cancel = async () => {
    if (reservation && step === 1) { try { await api.cancelReservation(reservation.reservation_id); } catch { /* déjà expirée */ } }
    closeBooking();
  };

  const expired = step === 1 && deadline && left === 0;
  const mmss = `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}`;
  const ticketTrip = useMemo(() => trip, [trip]);

  const summary = (
    <div className="summary">
      <div><b>{trip.departure_city} → {trip.arrival_city}</b><small>{trip.company_name} · {fmtDate(trip.departure_at)} · {fmtTime(trip.departure_at)}</small></div>
      <p>{fcfa(total)} <small>FCFA</small></p>
    </div>
  );

  // ---- Étape 0 : connexion si nécessaire ----
  if (!user) {
    return (
      <Modal onClose={closeBooking} label="Connexion requise pour réserver">
        <h2 className="modal__title">Connectez-vous pour réserver</h2>
        {summary}
        <AuthForm intro="Votre compte nous permet de vous envoyer votre billet et de sécuriser vos places." />
      </Modal>
    );
  }
  if (!isTraveler) {
    return (
      <Modal onClose={closeBooking} label="Compte non voyageur">
        <h2 className="modal__title">Réservation réservée aux voyageurs</h2>
        <p className="muted">Vous êtes connecté avec un compte {user.role === 'admin' ? 'administrateur' : 'de compagnie'}. Connectez-vous avec un compte voyageur pour réserver.</p>
        <button className="btn btn-primary btn-block" onClick={logout}>Changer de compte</button>
      </Modal>
    );
  }

  return (
    <Modal onClose={cancel} wide label="Réservation">
      <ol className="steps" aria-label="Étapes">
        {STEPS.map((s, i) => <li key={s} className={i === step ? 'on' : i < step ? 'done' : ''}><i>{i < step ? '✓' : i + 1}</i>{s}</li>)}
      </ol>

      {step === 0 && (
        <>
          <h2 className="modal__title">Choisissez vos places</h2>
          {summary}
          {loadingSeats ? <p className="center muted"><Loader2 className="spin" /> Chargement du plan…</p>
            : <SeatMap seats={seats} selected={selected} onToggle={toggle} />}
          {error && <p className="alert alert--error" role="alert">{error}</p>}
          <button className="btn btn-primary btn-block" disabled={!selected.length || busy} onClick={reserve}>
            {busy && <Loader2 className="spin" size={18} />}
            {selected.length ? `Continuer · ${selected.length} place${selected.length > 1 ? 's' : ''} · ${fcfa(selected.length * trip.price)} FCFA` : 'Sélectionnez au moins une place'}
          </button>
        </>
      )}

      {step === 1 && (
        <>
          <h2 className="modal__title">Paiement</h2>
          {summary}
          <p className={`hold ${expired ? 'hold--out' : ''}`}>
            {expired ? 'Votre réservation a expiré. Recommencez pour choisir de nouvelles places.' : <>Places gardées pour vous : <b>{mmss}</b></>}
          </p>
          <p className="notice"><AlertCircle size={15} /> Mode démonstration : aucun montant réel n'est débité.</p>

          {phase === 'processing' ? (
            <div className="phone-sim">
              <div className="phone-sim__ring"><Smartphone size={28} /></div>
              <b>{method === 'mobile_money' ? 'Validez sur votre téléphone' : 'Traitement du paiement…'}</b>
              <p className="muted">{method === 'mobile_money' ? `Une demande de ${fcfa(total)} FCFA a été envoyée au ${phone || 'numéro indiqué'}. Saisissez votre code secret.` : 'Un instant, nous confirmons votre paiement.'}</p>
              <Loader2 className="spin" />
            </div>
          ) : phase === 'failed' ? (
            <div className="phone-sim">
              <AlertCircle size={36} color="#E5484D" />
              <b>Paiement refusé</b>
              <p className="muted">La transaction simulée a échoué. Vos places restent gardées le temps du compte à rebours.</p>
              <button className="btn btn-primary" onClick={() => setPhase('form')}>Réessayer</button>
            </div>
          ) : (
            <>
              <div className="tabs tabs--pay" role="tablist">
                {[['mobile_money', 'Mobile Money', Smartphone], ['card', 'Carte', CreditCard], ['cash', 'Espèces', Banknote]].map(([k, l, Ic]) => (
                  <button key={k} role="tab" aria-selected={method === k} className={method === k ? 'on' : ''} onClick={() => setMethod(k)}><Ic size={16} /> {l}</button>
                ))}
              </div>

              {method === 'mobile_money' && (
                <>
                  <div className="ops">
                    {OPERATORS.map((o) => (
                      <button key={o.id} type="button" aria-pressed={operator === o.id} className={operator === o.id ? 'on' : ''} onClick={() => setOperator(o.id)}>
                        <i style={{ background: o.color, color: o.dark ? '#111' : '#fff' }}>{o.name[0]}</i>{o.name}
                      </button>
                    ))}
                  </div>
                  <label className="field"><span>Numéro de téléphone</span>
                    <div className="phone"><em>+225</em><input inputMode="tel" placeholder="07 00 00 00 00" value={phone} onChange={(e) => setPhone(e.target.value)} /></div>
                  </label>
                </>
              )}
              {method === 'card' && (
                <>
                  <label className="field"><span>Numéro de carte</span><input inputMode="numeric" placeholder="4242 4242 4242 4242" value={card.number} onChange={(e) => setCard({ ...card, number: e.target.value })} /></label>
                  <div className="grid2">
                    <label className="field"><span>Expiration</span><input placeholder="MM/AA" value={card.exp} onChange={(e) => setCard({ ...card, exp: e.target.value })} /></label>
                    <label className="field"><span>Code CVC</span><input inputMode="numeric" placeholder="123" value={card.cvc} onChange={(e) => setCard({ ...card, cvc: e.target.value })} /></label>
                  </div>
                </>
              )}
              {method === 'cash' && <p className="muted">Réglez en espèces à la gare routière avant l'embarquement. Votre billet est émis dès la confirmation.</p>}

              {error && <p className="alert alert--error" role="alert">{error}</p>}
              <button className="btn btn-primary btn-block" disabled={expired || (method === 'mobile_money' && phone.replace(/\D/g, '').length < 8)} onClick={() => pay(true)}>
                Payer {fcfa(total)} FCFA
              </button>
              <div className="row-between">
                <button className="linkbtn" onClick={cancel}>Annuler la réservation</button>
                <button className="linkbtn" onClick={() => pay(false)} disabled={expired}>Simuler un échec</button>
              </div>
            </>
          )}
        </>
      )}

      {step === 2 && (
        <>
          <div className="success"><CheckCircle2 size={34} /><h2 className="modal__title">Réservation confirmée</h2>
            <p className="muted">Référence {reservation?.reference}. Bon voyage !</p></div>
          {tickets.length ? tickets.map((t) => <TicketCard key={t.id} ticket={t} trip={ticketTrip} user={user} />)
            : <p className="muted center">Votre billet est en cours de génération. Retrouvez-le dans « Mes billets ».</p>}
          <button className="btn btn-primary btn-block" onClick={() => { closeBooking(); nav('/mes-billets'); }}>Voir mes billets</button>
        </>
      )}
    </Modal>
  );
}
