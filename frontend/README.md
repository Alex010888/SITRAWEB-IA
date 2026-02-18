# SITRACABAÑA - Frontend Admin Panel

Panel de administración en React + TypeScript para SITRACABAÑA.

## 🚀 Stack Tecnológico

- **React 18** + **TypeScript**
- **Vite** (build tool)
- **React Router** (rutas)
- **Axios** (HTTP client)
- **Context API** (gestión de estado)

## 📦 Instalación

### 1. Instalar dependencias

```bash
cd frontend
npm install
```

### 2. Configurar variables de entorno

El archivo `.env` ya está configurado:

```env
VITE_API_URL=http://localhost/sitra_web/backend/public/api
```

### 3. Iniciar servidor de desarrollo

```bash
npm run dev
```

La app estará disponible en: `http://localhost:3000`

## 🔐 Credenciales de prueba

- **Email**: `admin@sitracabana.org`
- **Password**: `admin123`

## 📁 Estructura del proyecto

```
frontend/
├── src/
│   ├── components/
│   │   └── ProtectedRoute.tsx    # HOC para rutas protegidas
│   ├── contexts/
│   │   └── AuthContext.tsx       # Context de autenticación
│   ├── pages/
│   │   ├── LoginPage.tsx         # Página de login
│   │   └── DashboardPage.tsx     # Dashboard principal
│   ├── services/
│   │   └── api.ts                # Cliente HTTP (Axios)
│   ├── styles/
│   │   ├── App.css
│   │   ├── Login.css
│   │   └── Dashboard.css
│   ├── types/
│   │   └── index.ts              # Tipos TypeScript
│   ├── App.tsx                   # Componente principal
│   └── main.tsx                  # Entry point
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── .env
```

## ✨ Características implementadas

### ✅ Autenticación
- Login con email/password
- Almacenamiento de JWT en localStorage
- Logout
- Context de autenticación global
- Interceptor para agregar token automáticamente

### ✅ Rutas
- Rutas protegidas (requieren autenticación)
- Redirección automática a `/login` si no está autenticado
- Redirección a `/dashboard` después del login

### ✅ Dashboard
- Información del usuario autenticado
- Lista de usuarios desde la API
- Estadísticas básicas
- Logout

### ✅ Seguridad
- Token JWT en header `Authorization: Bearer <token>`
- Renovación automática de token (si implementas refresh token en backend)
- Redirección automática si token expira (401)

## 🎨 Colores de marca

- **Verde**: `#178a3a` (primary)
- **Rojo**: `#b10f2e` (danger)
- **Negro**: `#0b0f14` (dark)

## 📡 API Endpoints utilizados

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Usuario actual |
| GET | `/users` | Listar usuarios |

## 🔧 Scripts disponibles

```bash
# Desarrollo
npm run dev

# Build para producción
npm run build

# Preview del build
npm run preview

# Linting
npm run lint
```

## 🌐 Proxy configurado

El servidor de desarrollo (Vite) tiene un proxy configurado para evitar problemas de CORS:

```ts
proxy: {
  '/api': {
    target: 'http://localhost/sitra_web/backend/public',
    changeOrigin: true,
  }
}
```

Esto significa que puedes hacer requests a `/api/auth/login` y se redirigirán automáticamente al backend.

## 🚀 Build para producción

```bash
npm run build
```

Los archivos optimizados se generarán en la carpeta `dist/`.

Para servir:

```bash
npm run preview
```

## 📝 Próximas mejoras sugeridas

- [ ] Refresh token automático
- [ ] Gestión completa de usuarios (crear, editar, eliminar)
- [ ] Módulo de noticias
- [ ] Módulo de galería
- [ ] Módulo de documentos
- [ ] Módulo de directiva
- [ ] Gestión de afiliaciones
- [ ] Notificaciones toast
- [ ] Loading states mejorados
- [ ] Validación de formularios con Yup/Zod
- [ ] Tests unitarios (Vitest + React Testing Library)

## 🐛 Troubleshooting

### "Network Error" al hacer login

- Verifica que Apache esté corriendo en XAMPP
- Verifica que el backend esté en: `http://localhost/sitra_web/backend/public/api`
- Revisa la consola del navegador para más detalles

### Token expira muy rápido

- Ajusta `JWT_EXPIRATION` en `backend/.env` (está en segundos)
- Implementa refresh token para renovación automática

### CORS errors

- Asegúrate que el backend tenga los headers CORS correctos
- El backend ya los tiene configurados en `routes/api.php`

## 📄 Licencia

Proyecto privado - SITRACABAÑA © 2026
