/**
 * Navbar: logo/título, nombre de usuario y botón Cerrar sesión.
 */

import { useAuth } from '../auth/AuthContext';

export default function Navbar() {
  const { user, logout } = useAuth();

  return (
    <header className="navbar">
      <div className="navbar-brand">Admin SITRACABAÑA</div>
      <div className="navbar-actions">
        <span className="navbar-user">{user?.nombre || user?.email}</span>
        <button type="button" className="btn btn-sm" onClick={logout}>
          Cerrar sesión
        </button>
      </div>
    </header>
  );
}
