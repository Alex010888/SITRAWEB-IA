import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import '../styles/Dashboard.css';

const LANDING_URL = import.meta.env.VITE_LANDING_URL || '';

const DashboardPage: React.FC = () => {
  const { user } = useAuth();

  return (
    <>
      <div className="welcome-card">
        <h2>👋 Bienvenido, {user?.nombre}</h2>
        <p>Has iniciado sesión en el panel de administración de SITRACABAÑA. Desde aquí puedes editar el contenido de la landing pública.</p>
        <div className="stats">
          <div className="stat-card">
            <span className="stat-icon">🔐</span>
            <div>
              <strong>{user?.rol}</strong>
              <span>Tu rol</span>
            </div>
          </div>
          <div className="stat-card">
            <span className="stat-icon">🌐</span>
            <div>
              <strong>Landing</strong>
              <span>Secciones y medios</span>
            </div>
          </div>
        </div>
      </div>

      <div className="section landing-block">
        <h3>Administrar landing pública</h3>
        <p className="landing-block-desc">El contenido que se muestra en el sitio público se gestiona aquí. Edita textos, títulos e imágenes.</p>
        <div className="landing-cards">
          <Link to="/dashboard/sections" className="landing-card">
            <span className="landing-card-title">Secciones</span>
            <span className="landing-card-desc">Títulos, textos y bloques de la página (hero, nosotros, contacto, etc.)</span>
          </Link>
          <Link to="/dashboard/media" className="landing-card">
            <span className="landing-card-title">Medios</span>
            <span className="landing-card-desc">Imágenes y PDFs para el sitio. Asócialos a una sección si quieres.</span>
          </Link>
        </div>
        {LANDING_URL && (
          <p className="landing-preview-link">
            <a href={LANDING_URL} target="_blank" rel="noopener noreferrer" className="btn-primary" style={{ display: 'inline-block', width: 'auto', padding: '10px 24px' }}>
              Ver sitio público →
            </a>
          </p>
        )}
      </div>

      {user?.rol === 'superadmin' && (
        <div className="section">
          <h3>Sistema</h3>
          <p className="landing-block-desc">Gestiona los usuarios que pueden acceder al panel.</p>
          <Link to="/dashboard/users" className="btn-primary" style={{ display: 'inline-block', width: 'auto', padding: '10px 24px' }}>
            Ir a Usuarios
          </Link>
        </div>
      )}
    </>
  );
};

export default DashboardPage;
