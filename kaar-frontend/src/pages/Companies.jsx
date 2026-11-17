import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Building2, Loader2, Phone } from 'lucide-react';
import TripCard from '../components/TripCard';
import { api } from '../api';

const Logo = ({ c }) => (c.logo ? <img src={c.logo} alt="" className="co__logo" /> : <span className="co__logo co__logo--ph">{c.name?.[0]}</span>);

export function Companies() {
  const [list, setList] = useState(null);
  useEffect(() => { api.companies().then(setList).catch(() => setList([])); }, []);
  return (
    <div className="page container">
      <h1 className="page__title">Nos compagnies partenaires</h1>
      {!list && <p className="center"><Loader2 className="spin" /></p>}
      <div className="cogrid">
        {list?.map((c) => (
          <Link key={c.id} to={`/compagnies/${c.id}`} className="co">
            <Logo c={c} /><b>{c.name}</b>
            <p className="muted">{c.description || 'Transport interurbain de voyageurs.'}</p>
          </Link>
        ))}
      </div>
      {list?.length === 0 && <div className="empty"><b>Aucune compagnie pour le moment</b></div>}
    </div>
  );
}

export function CompanyDetail() {
  const { id } = useParams();
  const [c, setC] = useState(null);
  const [trips, setTrips] = useState([]);
  useEffect(() => {
    api.company(id).then(setC).catch(() => {});
    api.trips().then((all) => setTrips(all.filter((t) => String(t.company_id) === String(id)))).catch(() => {});
  }, [id]);
  if (!c) return <div className="page container center"><Loader2 className="spin" /></div>;
  return (
    <div className="page container">
      <Link to="/compagnies" className="back">← Toutes les compagnies</Link>
      <div className="codetail"><Logo c={c} />
        <div><h1>{c.name}</h1><p className="muted">{c.description}</p>
          {c.phone && <p className="muted"><Phone size={14} /> {c.phone}</p>}</div></div>
      <h2 className="page__sub"><Building2 size={20} /> Voyages programmés</h2>
      <div className="stack">{trips.map((t) => <TripCard key={t.id} trip={t} />)}</div>
      {!trips.length && <div className="empty"><b>Aucun voyage disponible</b><p>Cette compagnie n'a pas de départ ouvert à la réservation.</p></div>}
    </div>
  );
}
