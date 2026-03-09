import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import apiService from '../services/api';
import { User, AuthContextType } from '../types';

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe usarse dentro de AuthProvider');
  }
  return context;
};

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Cargar usuario desde localStorage al montar
  useEffect(() => {
    const storedToken = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');

    if (storedToken && storedUser) {
      setToken(storedToken);
      setUser(JSON.parse(storedUser));
    }

    setIsLoading(false);
  }, []);

  const login = async (email: string, password: string) => {
    try {
      const response = await apiService.login(email, password);
      // API devuelve { success, message, data: { token, user } }
      const data = response?.data ?? response;
      const tokenVal = data?.token ?? response?.token;
      const userVal = data?.user ?? response?.user;

      if (response?.success && tokenVal && userVal) {
        localStorage.setItem('token', tokenVal);
        localStorage.setItem('user', JSON.stringify(userVal));
        setToken(tokenVal);
        setUser(userVal);
      } else {
        throw new Error(response?.message || data?.message || 'Error al iniciar sesión');
      }
    } catch (error: any) {
      const msg = error.response?.data?.message ?? error.message ?? 'Error al iniciar sesión';
      const detail = error.response?.status === 401 ? ' Revisa email y contraseña.' : '';
      throw new Error(msg + detail);
    }
  };

  const logout = () => {
    // Limpiar localStorage
    localStorage.removeItem('token');
    localStorage.removeItem('user');

    // Limpiar estado
    setToken(null);
    setUser(null);

    // Llamar al endpoint de logout (opcional, solo para logging)
    apiService.logout().catch(() => {
      // Ignorar errores en logout
    });
  };

  const value: AuthContextType = {
    user,
    token,
    login,
    logout,
    isAuthenticated: !!token && !!user,
    isLoading,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
