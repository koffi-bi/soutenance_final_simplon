import { useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { LayoutDashboard, LogOut, Menu, Ticket, X } from 'lucide-react';
import Logo from './Logo';
import { useAuth, roleHome } from '../context/AuthContext';
import { useUI } from '../context/UIContext';

export default function Navbar() {
  const { user, logout } = useAuth();
  const { openAuth } = useUI();
  const { pathname } = useLocation();
  const nav = useNavigate();
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState(false);
  const home = pathname === '/';
  const dash = roleHome(user?.role);
  const initials = user ? `${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase() : '';

  return (
    <header className={`nav ${home ? 'nav--hero' : ''}`}>
      <div className="container nav__in">
        <Logo dark />
        <button className="nav__burger" onClick={() => setOpen(!open)} aria-label="Menu">{open ? <X /> : <Menu />}</button>
        <nav className={`nav__links ${open ? 'open' : ''}`} onClick={() => setOpen(false)}>
          <NavLink to="/trajets">Destinations</NavLink>
          <NavLink to="/compagnies">Compagnies</NavLink>
          <a href="/#comment-ca-marche" onClick={(e) => { if (home) { e.preventDefault(); document.getElementById('comment-ca-marche')?.scrollIntoView({ behavior: 'smooth' }); } }}>Comment ça marche ?</a>
        </nav>
        <div className={`nav__right ${open ? 'open' : ''}`}>
          <span className="nav__lang">FR</span>
          {!user ? (
            <>
              <button className="nav__login" onClick={() => openAuth('login')}>Se connecter</button>
              <button className="btn btn-primary btn-sm" onClick={() => openAuth('register')}>Créer un compte</button>
            </>
          ) : (
            <div className="usermenu">
              <button className="avatar" onClick={() => setMenu(!menu)} aria-label="Mon compte">{initials}</button>
              {menu && (
                <div className="usermenu__pop" onClick={() => setMenu(false)}>
                  <p><b>{user.first_name} {user.last_name}</b><small>{user.email}</small></p>
                  {dash !== '/' && <Link to={dash}><LayoutDashboard size={16} /> Tableau de bord</Link>}
                  {user.role === 'traveler' && <Link to="/mes-billets"><Ticket size={16} /> Mes billets</Link>}
                  <button onClick={async () => { await logout(); nav('/'); }}><LogOut size={16} /> Se déconnecter</button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
