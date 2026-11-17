import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import SearchBar from '../components/SearchBar';
import TripCard from '../components/TripCard';
import { api } from '../api';
import { fmtDate } from '../lib/format';

export default function Trips() {
  const [sp, setSp] = useSearchParams();
  const [dir, setDir] = useState('go');
  const [trips, setTrips] = useState(null);
  const [error, setError] = useState('');
  const from = sp.get('from') || '', to = sp.get('to') || '', date = sp.get('date') || '', ret = sp.get('ret') || '', pax = sp.get('pax') || '1';

  useEffect(() => {
    setTrips(null); setError('');
    const back = dir === 'back';
    api.trips({ departure_city_id: back ? to : from, arrival_city_id: back ? from : to, departure_date: back ? ret : date })
      .then(setTrips).catch((e) => { setError(e.message); setTrips([]); });
  }, [from, to, date, ret, dir]);

  const onSearch = (v) => { setDir('go'); setSp(Object.fromEntries(Object.entries(v).filter(([, x]) => x))); };

  return (
    <div className="page container">
      <SearchBar compact initial={{ from, to, date, ret, pax }} onSearch={onSearch} />
      {ret && (
        <div className="tabs tabs--dir">
          <button className={dir === 'go' ? 'on' : ''} onClick={() => setDir('go')}>Aller · {fmtDate(date)}</button>
          <button className={dir === 'back' ? 'on' : ''} onClick={() => setDir('back')}>Retour · {fmtDate(ret)}</button>
        </div>
      )}
      <h1 className="page__title">{trips ? `${trips.length} voyage${trips.length > 1 ? 's' : ''} disponible${trips.length > 1 ? 's' : ''}` : 'Recherche en cours…'}</h1>
      {error && <p className="alert alert--error">{error}</p>}
      {!trips && <p className="center"><Loader2 className="spin" /></p>}
      <div className="stack">{trips?.map((t) => <TripCard key={t.id} trip={t} pax={+pax} />)}</div>
      {trips?.length === 0 && !error && <div className="empty"><b>Aucun voyage trouvé</b><p>Essayez une autre date ou une autre destination.</p></div>}
    </div>
  );
}
