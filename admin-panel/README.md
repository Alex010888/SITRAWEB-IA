# Admin Panel - SITRACABAÑA

Panel de administración en React (Vite + JavaScript) para gestionar el contenido de la landing y usuarios. Consume la API REST existente (JWT, RBAC, CMS, Media).

## Requisitos

- Node 18+
- API backend en marcha (por ejemplo `http://localhost/sitra_web/backend/public`)

## Instalación

```bash
cd admin-panel
npm install
```

## Configuración

Copia `.env.example` a `.env` y ajusta la URL de la API si es necesario:

```env
VITE_API_BASE_URL=http://localhost/sitra_web/backend/public
# Opcional: URL de la landing pública para el enlace "Ver sitio público"
# VITE_LANDING_URL=http://localhost/sitra_web/public
```

## Ejecución

```bash
npm run dev
```

Abre `http://localhost:3001`. Credenciales de prueba: `admin@sitracabana.org` / `admin123`.

## Estructura

```
src/
├── api/http.js          # Cliente Fetch con JWT y manejo 401
├── auth/
│   ├── AuthContext.jsx  # Estado de login y canManageUsers
│   └── ProtectedRoute.jsx
├── pages/
│   ├── Login.jsx
│   ├── Dashboard.jsx
│   ├── Sections.jsx     # Editor de secciones CMS
│   ├── Media.jsx        # Subir/listar/eliminar medios
│   └── Users.jsx        # Solo superadmin
├── components/
│   ├── Navbar.jsx
│   ├── Sidebar.jsx      # Enlaces según rol
│   ├── SectionEditor.jsx
│   └── MediaUploader.jsx
├── router/AppRouter.jsx
├── styles/index.css
├── App.jsx
└── main.jsx
```

## Rutas

- `/login` — Público
- `/` — Dashboard (protegido)
- `/sections` — Editar secciones (protegido, `edit_content`)
- `/media` — Medios (protegido, `edit_content`)
- `/users` — Usuarios (protegido, rol `superadmin`)

## Ejemplo de llamadas API (vía `http.js`)

```javascript
import * as http from './api/http';

// Login (sin token)
const res = await http.post('/auth/login', { email, password });
// res.data.token, res.data.user

// Listar secciones (con token en localStorage)
const sections = await http.get('/sections');

// Actualizar sección
await http.put('/sections/hero', { content: { title: 'Nuevo título' } });

// Subir archivo
const formData = new FormData();
formData.append('file', file);
formData.append('section_key', 'hero');
await http.post('/media/upload', formData);

// Listar usuarios (superadmin)
const users = await http.get('/users');
```

## Build

```bash
npm run build
```

Salida en `dist/`. Sirve con `npm run preview` para probar.
