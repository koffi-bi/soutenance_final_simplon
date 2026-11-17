import { useEffect, useState } from 'react';
import { ArrowLeftRight, Calendar, MapPin, Search, Users } from 'lucide-react';
import { api } from '../api';
import { todayISO } from '../lib/format';

export default function SearchBar({ initial = {}, onSearch, compact = false }) {
  const [cities, setCities] = useState([]);
  const [roundTrip, setRoundTrip] = useState(!!initial.ret);
  const [v, setV] = useState({ from: initial.from || '', to: initial.to || '', date: initial.date || todayISO(), ret: initial.ret || '', pax: initial.pax || '1' });

  useEffect(() => {
    api.cities().then((c) => {
      setCities(c);
      setV((s) => ({ ...s, from: s.from || c[0]?.id || '', to: s.to || c[1]?.id || '' }));
    }).catch(() => {});
  }, []);

  const swap = () => setV((s) => ({ ...s, from: s.to, to: s.from }));
  const submit = (e) => { e.preventDefault(); onSearch({ ...v, ret: roundTrip ? v.ret : '' }); };
  const opts = cities.map((c) => <option key={c.id} value={c.id}>{c.name}</option>);

  return (
    <form className={`search ${compact ? 'search--compact' : ''}`} onSubmit={submit}>
      <div className="search__modes" role="radiogroup">
        {[['Aller simple', false], ['Aller-retour', true]].map(([l, rt]) => (
          <button type="button" key={l} role="radio" aria-checked={roundTrip === rt} className={roundTrip === rt ? 'on' : ''} onClick={() => setRoundTrip(rt)}>
            <i /> {l}
          </button>
        ))}
      </div>
      <div className={`search__row ${roundTrip ? 'has-return' : ''}`}>
        <label className="sf">
          <span>Départ</span>
          <div><MapPin size={17} /><select value={v.from} onChange={(e) => setV({ ...v, from: e.target.value })}>{opts}</select></div>
        </label>
        <button type="button" className="swap" onClick={swap} aria-label="Inverser départ et destination"><ArrowLeftRight size={15} /></button>
        <label className="sf">
          <span>Destination</span>
          <div><MapPin size={17} /><select value={v.to} onChange={(e) => setV({ ...v, to: e.target.value })}>{opts}</select></div>
        </label>
        <label className="sf">
          <span>Date du voyage</span>
          <div><Calendar size={17} /><input type="date" min={todayISO()} value={v.date} onChange={(e) => setV({ ...v, date: e.target.value })} /></div>
        </label>
        {roundTrip && (
          <label className="sf">
            <span>Date de retour</span>
            <div><Calendar size={17} /><input type="date" min={v.date} value={v.ret} onChange={(e) => setV({ ...v, ret: e.target.value })} required /></div>
          </label>
        )}
        <label className="sf">
          <span>Voyageurs</span>
          <div><Users size={17} /><select value={v.pax} onChange={(e) => setV({ ...v, pax: e.target.value })}>
            {[1, 2, 3, 4, 5, 6].map((n) => <option key={n} value={n}>{n} adulte{n > 1 ? 's' : ''}</option>)}
          </select></div>
        </label>
        <button className="btn btn-primary btn-search"><Search size={18} /> Rechercher</button>
      </div>
    </form>
  );
}
