import { AuthProvider } from './auth/AuthContext';
import AppRouter from './router/AppRouter';
import './styles/index.css';

export default function App() {
  return (
    <AuthProvider>
      <AppRouter />
    </AuthProvider>
  );
}
