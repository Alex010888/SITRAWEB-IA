/**
 * Sidebar: enlaces por rol. Agrupa "Landing pública" (Secciones + Medios).
 */

import { NavLink } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

const LANDING_URL = import.meta.env.VITE_LANDING_URL || '';

export default function Sidebar() {
  const { canManageUsers } = useAuth();

  return (
    <aside className="sidebar">
      <nav>
        <NavLink to="/" end className={({ isActive }) => (isActive ? 'active' : '')}>
          Inicio
        </NavLink>
        <div className="sidebar-group">
          <span className="sidebar-group-title">Landing pública</span>
          <NavLink to="/sections" className={({ isActive }) => (isActive ? 'active' : '')}>
            Secciones
          </NavLink>
          <NavLink to="/media" className={({ isActive }) => (isActive ? 'active' : '')}>
            Medios
          </NavLink>
          {LANDING_URL && (
            <a href={LANDING_URL} target="_blank" rel="noopener noreferrer" className="sidebar-external">
              Ver sitio público →
            </a>
          )}
        </div>
        {canManageUsers && (
          <>
            <div className="sidebar-group">
              <span className="sidebar-group-title">Sistema</span>
              <NavLink to="/users" className={({ isActive }) => (isActive ? 'active' : '')}>
                Usuarios
              </NavLink>
            </div>
          </>
        )}
      </nav>
    </aside>
  );
}
