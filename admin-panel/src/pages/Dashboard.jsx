/**
 * Dashboard: bienvenida, rol y administración de la landing pública.
 */

import { Link } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

const LANDING_URL = import.meta.env.VITE_LANDING_URL || '';

export default function Dashboard() {
  const { user } = useAuth();
  const name = user?.nombre || user?.email || 'Usuario';

  return (
    <div className="page dashboard-page">
      <h1>Panel de administración</h1>
      <p className="welcome">Hola, <strong>{name}</strong>. Tu rol es <strong>{user?.rol || '—'}</strong>.</p>

      <div className="dashboard-block landing-block">
        <h2>Administrar landing pública</h2>
        <p className="block-desc">El contenido que ves en el sitio público se gestiona desde aquí. Edita textos, títulos e imágenes.</p>
        <div className="quick-cards">
          <Link to="/sections" className="quick-card">
            <span className="quick-card-title">Secciones</span>
            <span className="quick-card-desc">Títulos, textos y bloques de la página (hero, nosotros, contacto, etc.)</span>
          </Link>
          <Link to="/media" className="quick-card">
            <span className="quick-card-title">Medios</span>
            <span className="quick-card-desc">Imágenes y PDFs para el sitio. Asócialos a una sección si quieres.</span>
          </Link>
        </div>
        {LANDING_URL && (
          <p className="landing-preview">
            <a href={LANDING_URL} target="_blank" rel="noopener noreferrer" className="btn btn-primary">
              Ver sitio público →
            </a>
          </p>
        )}
      </div>

      <div className="quick-links">
        <h2>Más</h2>
        <ul>
          {user?.rol === 'superadmin' && (
            <li><Link to="/users">Usuarios</Link> — Gestionar quién puede entrar al panel.</li>
          )}
        </ul>
      </div>
    </div>
  );
}
