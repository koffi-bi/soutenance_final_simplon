import Logo from './Logo';

export default function Footer() {
  return (
    <footer className="footer">
      <div className="container footer__in">
        <Logo to="/" />
        <p>Voyagez simplement. Voyagez sereinement.</p>
        <small>© {new Date().getFullYear()} Kaar. Tous droits réservés.</small>
      </div>
    </footer>
  );
}
