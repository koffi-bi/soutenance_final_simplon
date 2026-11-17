import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ArrowRight, Bot, ChevronRight, Clock, Map as MapIcon } from 'lucide-react';
import SearchBar from '../components/SearchBar';
import { api } from '../api';
import { useUI } from '../context/UIContext';
import { fcfa, fmtDuration } from '../lib/format';

const STATS = [['+24 000', 'voyageurs satisfaits'], ['35', 'compagnies partenaires'], ['120+', 'destinations desservies'], ['4,8/5', 'note moyenne voyageurs']];
const TINTS = ['orange', 'blue', 'green'];
const STEPS = [
  ['Cherchez', 'Choisissez votre trajet, votre date et comparez les compagnies sans créer de compte.'],
  ['Choisissez votre siège', 'Sélectionnez votre place sur le plan du car. Un compte est demandé uniquement à ce moment-là.'],
  ['Payez et voyagez', 'Réglez par Mobile Money et recevez votre billet avec QR code, téléchargeable en PDF.'],
];

export default function Home() {
  const nav = useNavigate();
  const { openChat } = useUI();
  const [trips, setTrips] = useState([]);

  useEffect(() => { api.trips().then(setTrips).catch(() => {}); }, []);

  // Regroupe les voyages par ligne : prix mini, durée et nombre de départs
  const routes = useMemo(() => {
    const m = new Map();
    trips.forEach((t) => {
      const k = `${t.departure_city_id}-${t.arrival_city_id}`;
      const r = m.get(k) || { k, from: t.departure_city, to: t.arrival_city, fromId: t.departure_city_id, toId: t.arrival_city_id, price: Infinity, minutes: t.estimated_duration_minutes, n: 0 };
      r.price = Math.min(r.price, t.price); r.n += 1; m.set(k, r);
    });
    return [...m.values()].sort((a, b) => b.n - a.n).slice(0, 3);
  }, [trips]);

  const search = (v) => nav(`/trajets?${new URLSearchParams(Object.entries({ from: v.from, to: v.to, date: v.date, ret: v.ret, pax: v.pax }).filter(([, x]) => x))}`);

  return (
    <>
      <section className="hero">
        <div className="hero__img" />
        <div className="container hero__in">
          <span className="pill"><i /> Le voyage interurbain, réinventé</span>
          <h1>Votre prochain voyage commence <em>ici.</em></h1>
          <p>Comparez les meilleures compagnies, choisissez votre siège et voyagez l'esprit tranquille, partout en Côte d'Ivoire.</p>
        </div>
      </section>

      <div className="container search-wrap"><SearchBar onSearch={search} /></div>

      <div className="container">
        <div className="statsband">
          {STATS.map(([n, l]) => <div key={l}><b>{n}</b><span>{l}</span></div>)}
        </div>
      </div>

      <section className="section container">
        <p className="eyebrow">Inspiration</p>
        <div className="section__head">
          <div><h2>Les trajets préférés des voyageurs</h2><p className="muted">Partez à la découverte de la Côte d'Ivoire au meilleur prix.</p></div>
          <Link to="/trajets" className="more">Voir tous les trajets <ArrowRight size={17} /></Link>
        </div>
        <div className="routes">
          {(routes.length ? routes : [
            { k: 1, from: 'Abidjan', to: 'Yamoussoukro', minutes: 195, n: 18, price: 5000 },
            { k: 2, from: 'Abidjan', to: 'Bouaké', minutes: 330, n: 12, price: 8000 },
            { k: 3, from: 'Abidjan', to: 'San-Pédro', minutes: 405, n: 8, price: 10000 },
          ]).map((r, i) => (
            <Link key={r.k} className="route" to={r.fromId ? `/trajets?from=${r.fromId}&to=${r.toId}` : '/trajets'}>
              <div className="route__top"><span className={`ic ic--${TINTS[i % 3]}`}><MapIcon size={18} /></span><small>À partir de</small></div>
              <div className="route__cities"><b>{r.from}</b><i /><b>{r.to}</b></div>
              <div className="route__meta"><span><Clock size={14} /> {fmtDuration(r.minutes)}</span><span>{r.n} départ{r.n > 1 ? 's' : ''} / jour</span></div>
              <div className="route__foot"><p><b>{fcfa(r.price)}</b> <small>FCFA</small></p><span className="go"><ChevronRight size={18} /></span></div>
            </Link>
          ))}
        </div>
      </section>

      <section id="comment-ca-marche" className="section container how">
        <h2>Comment ça marche ?</h2>
        <ol>
          {STEPS.map(([t, d], i) => <li key={t}><i>{i + 1}</i><div><b>{t}</b><p>{d}</p></div></li>)}
        </ol>
      </section>

      <section className="container">
        <div className="kokoband">
          <span className="kokoband__ic"><Bot size={30} /></span>
          <div><p className="eyebrow">Assistant de voyage</p><h2>Une question ? Demandez à Kôkô.</h2>
            <p>Horaires, disponibilités, réservations… votre assistant vous répond instantanément.</p></div>
          <button className="btn btn-white" onClick={openChat}>Démarrer une conversation <ArrowRight size={17} /></button>
        </div>
      </section>
    </>
  );
}
