import React from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import '../styles/Dashboard.css';

const LANDING_URL = import.meta.env.VITE_LANDING_URL || '';

const AdminLayout: React.FC = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const isSuperadmin = user?.rol === 'superadmin';

  const handleLogout = () => {
    if (window.confirm('¿Cerrar sesión?')) {
      logout();
      navigate('/login');
    }
  };

  return (
    <div className="dashboard">
      <header className="dashboard-header">
        <div className="container">
          <div className="header-content">
            <div className="logo-section">
              <h1>SITRACABAÑA</h1>
              <span className="subtitle">Panel de Administración</span>
            </div>
            <div className="user-section">
              <div className="user-info">
                <strong>{user?.nombre}</strong>
                <span className="user-rol">{user?.rol}</span>
              </div>
              <button onClick={handleLogout} className="btn-logout">
                Cerrar Sesión
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="dashboard-layout">
        <aside className="dashboard-sidebar">
          <nav className="sidebar-nav">
            <NavLink to="/dashboard" end className={({ isActive }) => isActive ? 'sidebar-link active' : 'sidebar-link'}>
              Inicio
            </NavLink>
            <p className="sidebar-group-title">Landing pública</p>
            <NavLink to="/dashboard/sections" className={({ isActive }) => isActive ? 'sidebar-link active' : 'sidebar-link'}>
              Secciones
            </NavLink>
            <NavLink to="/dashboard/media" className={({ isActive }) => isActive ? 'sidebar-link active' : 'sidebar-link'}>
              Medios
            </NavLink>
            {LANDING_URL && (
              <a href={LANDING_URL} target="_blank" rel="noopener noreferrer" className="sidebar-link sidebar-external">
                Ver sitio público →
              </a>
            )}
            {isSuperadmin && (
              <>
                <p className="sidebar-group-title">Sistema</p>
                <NavLink to="/dashboard/users" className={({ isActive }) => isActive ? 'sidebar-link active' : 'sidebar-link'}>
                  Usuarios
                </NavLink>
              </>
            )}
          </nav>
        </aside>
        <main className="dashboard-main">
          <div className="container">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
