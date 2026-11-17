import { Link } from 'react-router-dom';

export function LogoMark({ size = 40 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 40 40" aria-hidden="true">
      <rect x="3" y="3" width="34" height="34" rx="11" fill="#FF6B1F" transform="rotate(-8 20 20)" />
      <path d="M12 20h15m-6-6 6 6-6 6" stroke="#fff" strokeWidth="2.6" fill="none" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export default function Logo({ dark = false, to = '/' }) {
  return (
    <Link to={to} className={`logo ${dark ? 'logo--dark' : ''}`} aria-label="Kaar, accueil">
      <LogoMark />
      <span className="logo__word">Kaar<i>.</i></span>
    </Link>
  );
}
