import React, { useEffect, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import apiService from '../services/api';
import { User } from '../types';
import '../styles/Dashboard.css';

const UsersPage: React.FC = () => {
  const { user } = useAuth();
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    try {
      setIsLoading(true);
      const response = await apiService.getUsers();
      if (response.success) setUsers(response.data);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al cargar usuarios');
    } finally {
      setIsLoading(false);
    }
  };

  if (user?.rol !== 'superadmin') {
    return (
      <div className="section">
        <p>No tienes permiso para ver esta página.</p>
      </div>
    );
  }

  return (
    <div>
      <h2 style={{ marginBottom: 16 }}>Usuarios del panel</h2>
      <p style={{ color: 'var(--color-text-light)', marginBottom: 20 }}>Gestiona quién puede acceder al panel de administración.</p>
      {error && <div className="alert alert-error">{error}</div>}
      {isLoading ? (
        <p>Cargando usuarios...</p>
      ) : (
        <div className="section">
          <div className="table-container">
            <table className="users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Fecha de creación</th>
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id}>
                    <td>{u.id}</td>
                    <td>{u.nombre}</td>
                    <td>{u.email}</td>
                    <td>
                      <span className={`badge badge-${u.rol}`}>{u.rol}</span>
                    </td>
                    <td>
                      <span className={`status ${u.activo ? 'active' : 'inactive'}`}>
                        {u.activo ? '✅ Activo' : '❌ Inactivo'}
                      </span>
                    </td>
                    <td>
                      {u.created_at ? new Date(u.created_at).toLocaleDateString('es-ES') : '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};

export default UsersPage;
