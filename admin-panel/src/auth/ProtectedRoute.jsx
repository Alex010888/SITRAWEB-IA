/**
 * ProtectedRoute: renderiza children solo si el usuario está autenticado.
 * Si no, redirige a /login. Opcionalmente requiere un permiso/rol (requiredRole).
 */

import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext';

export function ProtectedRoute({ children, requiredRole = null }) {
  const { isAuthenticated, isLoading, user } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="app-loading">
        <p>Cargando…</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (requiredRole && user?.rol !== requiredRole) {
    return (
      <div className="access-denied">
        <h2>Acceso denegado</h2>
        <p>No tienes permiso para ver esta sección.</p>
      </div>
    );
  }

  return children;
}
