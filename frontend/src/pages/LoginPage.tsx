import React, { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import '../styles/Login.css';

const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err: any) {
      setError(err.message || 'Error al iniciar sesión');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="login-container">
      <div className="login-box">
        <div className="login-header">
          <div className="logo">
            <svg width="60" height="60" viewBox="0 0 256 256">
              <defs>
                <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
                  <stop offset="0" stopColor="#178a3a"/>
                  <stop offset="0.55" stopColor="#0b0f14"/>
                  <stop offset="1" stopColor="#b10f2e"/>
                </linearGradient>
              </defs>
              <rect x="16" y="16" width="224" height="224" rx="44" fill="url(#g)"/>
              <path d="M78 150c10 20 28 32 50 32s40-12 50-32" fill="none" stroke="#fff" strokeWidth="12" strokeLinecap="round"/>
              <path d="M86 112h84" stroke="#fff" strokeWidth="12" strokeLinecap="round"/>
              <circle cx="96" cy="96" r="10" fill="#fff"/>
              <circle cx="160" cy="96" r="10" fill="#fff"/>
            </svg>
          </div>
          <h1>SITRACABAÑA</h1>
          <p>Panel de Administración</p>
        </div>

        <form onSubmit={handleSubmit} className="login-form">
          {error && (
            <div className="alert alert-error">
              <span>⚠️</span>
              {error}
            </div>
          )}

          <div className="form-group">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@sitracabana.org"
              required
              autoComplete="email"
              disabled={isLoading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Contraseña</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              autoComplete="current-password"
              disabled={isLoading}
            />
          </div>

          <button 
            type="submit" 
            className="btn-primary"
            disabled={isLoading}
          >
            {isLoading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
          </button>
        </form>

        <div className="login-footer">
          <p>
            <strong>Credenciales de prueba:</strong><br />
            admin@sitracabana.org / admin123
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
