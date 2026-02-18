/**
 * Users: listar, crear y editar usuarios (solo superadmin).
 * GET /api/users, POST /api/users, PUT /api/users/{id}
 */

import { useState, useEffect } from 'react';
import * as http from '../api/http';

const ROLES = ['editor', 'directivo', 'superadmin'];

export default function Users() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ nombre: '', email: '', password: '', rol: 'editor', activo: 1 });

  const loadUsers = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await http.get('/users');
      if (res.success && Array.isArray(res.data)) setUsers(res.data);
    } catch (err) {
      setError(err.message || 'Error al cargar usuarios');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    try {
      const res = await http.post('/users', {
        nombre: form.nombre.trim(),
        email: form.email.trim().toLowerCase(),
        password: form.password,
        rol: form.rol,
        activo: Number(form.activo),
      });
      if (res.success) {
        setSuccess('Usuario creado.');
        setForm({ nombre: '', email: '', password: '', rol: 'editor', activo: 1 });
        setShowForm(false);
        loadUsers();
      } else throw new Error(res.message || 'Error al crear');
    } catch (err) {
      setError(err.message || 'Error al crear usuario');
    }
  };

  const handleToggleActive = async (user) => {
    const newActive = user.activo ? 0 : 1;
    setError('');
    try {
      await http.put(`/users/${user.id}`, { activo: newActive });
      setSuccess(user.activo ? 'Usuario desactivado.' : 'Usuario activado.');
      setUsers((prev) => prev.map((u) => (u.id === user.id ? { ...u, activo: newActive } : u)));
    } catch (err) {
      setError(err.message || 'Error al actualizar');
    }
  };

  return (
    <div className="page users-page">
      <h1>Usuarios</h1>
      {success && <div className="alert alert-success" role="alert">{success}</div>}
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      <div className="users-toolbar">
        <button type="button" className="btn btn-primary" onClick={() => setShowForm(!showForm)}>
          {showForm ? 'Cancelar' : 'Nuevo usuario'}
        </button>
      </div>
      {showForm && (
        <form className="user-form card" onSubmit={handleCreate}>
          <h3>Crear usuario</h3>
          <label>Nombre <input type="text" value={form.nombre} onChange={(e) => setForm((f) => ({ ...f, nombre: e.target.value }))} required /></label>
          <label>Email <input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} required /></label>
          <label>Contraseña <input type="password" value={form.password} onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))} required minLength={6} /></label>
          <label>Rol <select value={form.rol} onChange={(e) => setForm((f) => ({ ...f, rol: e.target.value }))}>{ROLES.map((r) => <option key={r} value={r}>{r}</option>)}</select></label>
          <label>Activo <input type="checkbox" checked={!!form.activo} onChange={(e) => setForm((f) => ({ ...f, activo: e.target.checked ? 1 : 0 }))} /></label>
          <button type="submit" className="btn btn-primary">Crear</button>
        </form>
      )}
      {loading ? (
        <p>Cargando…</p>
      ) : (
        <div className="table-wrap">
          <table className="users-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Activo</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id}>
                  <td>{u.nombre}</td>
                  <td>{u.email}</td>
                  <td>{u.rol}</td>
                  <td>{u.activo ? 'Sí' : 'No'}</td>
                  <td>
                    <button type="button" className="btn btn-sm" onClick={() => handleToggleActive(u)}>
                      {u.activo ? 'Desactivar' : 'Activar'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
