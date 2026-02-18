# SITRACABAÑA - Backend API

Backend REST API en PHP puro con JWT, RBAC y CMS headless.

## 📦 Módulos Implementados

- ✅ **Módulo 1**: Autenticación y Usuarios (JWT)
- ✅ **Módulo 2**: Roles y Permisos (RBAC)
- ✅ **Módulo 3**: CMS / Sections (Headless CMS)

## 📁 Estructura

```
backend/
├── app/
│   ├── controllers/    # Auth, User, Permissions, Section
│   ├── models/         # User, Section
│   ├── middlewares/    # AuthMiddleware, RoleMiddleware
│   ├── services/       # JwtService, PermissionService, SectionService
│   └── config/         # database.php, env.php
├── routes/
│   └── api.php         # Definición de endpoints
├── public/
│   ├── index.php       # Entry point
│   └── .htaccess       # Rutas limpias + Authorization header
├── .env                # Variables de entorno
├── database_api.sql    # Schema usuarios
├── database_sections.sql # Schema CMS
├── MODULE_2_RBAC.md    # Documentación del sistema RBAC
├── MODULE_3_CMS.md     # Documentación del CMS
├── EXAMPLES_RBAC.sh    # Ejemplos cURL de RBAC
├── EXAMPLES_CMS.sh     # Ejemplos cURL de CMS
├── test_rbac.php       # Tests unitarios de permisos
└── README.md           # Este archivo
```

## 🚀 Instalación

### 1. Configurar Base de Datos

```bash
# Usuarios y autenticación:
mysql -u root -p < backend/database_api.sql

# CMS Sections:
mysql -u root -p < backend/database_sections.sql
```

### 2. Configurar .env

Edita `backend/.env`:

```env
DB_HOST=127.0.0.1
DB_NAME=sitra_web
DB_USER=root
DB_PASS=

JWT_SECRET=cambiar_en_produccion
JWT_EXPIRATION=3600
```

### 3. Configurar Apache

Asegúrate que `mod_rewrite` esté habilitado:

```apache
# En httpd.conf:
LoadModule rewrite_module modules/mod_rewrite.so

# Permitir .htaccess:
<Directory "C:/xampp/htdocs">
    AllowOverride All
</Directory>
```

### 4. Probar API

Base URL: `http://localhost/sitra_web/backend/public/api`

## 📡 Endpoints

### 🔓 Públicos

#### CMS - Obtener secciones (para Landing Page)
```http
GET /api/public/sections
GET /api/public/sections/{key}
```

Ejemplo:
```bash
curl http://localhost/sitra_web/backend/public/api/public/sections/hero
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@sitracabana.org",
  "password": "admin123"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@sitracabana.org",
      "rol": "superadmin"
    }
  }
}
```

#### Logout
```http
POST /api/auth/logout
```

### 🔒 Protegidos (requieren JWT)

Incluye el header en todas las requests:
```
Authorization: Bearer <token>
```

#### Obtener usuario actual
```http
GET /api/auth/me
Authorization: Bearer <token>
```

#### Obtener permisos del usuario actual
```http
GET /api/permissions/me
Authorization: Bearer <token>
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "rol": "directivo",
    "permissions": ["view_content", "edit_content", "view_analytics"],
    "can": {
      "view_content": true,
      "edit_content": true,
      "delete_content": false,
      "manage_users": false
    }
  }
}
```

#### Listar todos los roles (solo superadmin)
```http
GET /api/permissions/roles
Authorization: Bearer <token>
```

#### Listar usuarios
```http
GET /api/users
Authorization: Bearer <token>
```

#### Obtener usuario por ID
```http
GET /api/users/{id}
Authorization: Bearer <token>
```

#### Crear usuario (requiere manage_users)
```http
POST /api/users
Authorization: Bearer <token>
Content-Type: application/json

{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "password": "secreto123",
  "rol": "editor",
  "activo": 1
}
```

#### Actualizar usuario (requiere manage_users)
```http
PUT /api/users/{id}
Authorization: Bearer <token>
Content-Type: application/json

{
  "nombre": "Juan Actualizado",
  "rol": "directivo"
}
```

#### Desactivar usuario (requiere manage_users)
```http
DELETE /api/users/{id}
Authorization: Bearer <token>
```

#### CMS - Gestionar secciones (Admin)
```http
GET    /api/sections           # Lista todas
GET    /api/sections/{key}     # Obtiene una
PUT    /api/sections/{key}     # Actualiza/crea
DELETE /api/sections/{key}     # Elimina
Authorization: Bearer <token>
```

Ejemplo actualizar sección:
```bash
curl -X PUT http://localhost/sitra_web/backend/public/api/sections/hero \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"content":{"title":"Nuevo título","subtitle":"Nuevo subtítulo"}}'
```

## 🧪 Pruebas con cURL

Consulta `EXAMPLES_RBAC.sh` para ejemplos completos.

### Login
```bash
curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sitracabana.org","password":"admin123"}'
```

### Obtener permisos
```bash
curl -X GET http://localhost/sitra_web/backend/public/api/permissions/me \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### Listar usuarios (con token)
```bash
curl -X GET http://localhost/sitra_web/backend/public/api/users \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

## 🧪 Tests Automatizados

Ejecuta el test unitario del sistema RBAC:

```bash
php backend/test_rbac.php
```

## 🔐 Seguridad

- ✅ JWT sin sesiones
- ✅ Passwords hasheados con bcrypt
- ✅ PDO prepared statements
- ✅ Validación de inputs
- ✅ Middleware de autenticación (AuthMiddleware)
- ✅ Middleware de permisos (RoleMiddleware)
- ✅ Sistema RBAC centralizado (PermissionService)
- ✅ CORS configurado

## 🎯 Sistema de Permisos

### Roles disponibles:
- **superadmin**: Acceso total (todos los permisos)
- **directivo**: Gestión de contenidos + analytics
- **editor**: Solo edición de contenidos

### Permisos definidos:

| Permiso | Descripción | superadmin | directivo | editor |
|---------|-------------|------------|-----------|--------|
| `view_content` | Ver contenidos | ✅ | ✅ | ❌ |
| `edit_content` | Editar contenidos | ✅ | ✅ | ✅ |
| `delete_content` | Eliminar contenidos | ✅ | ❌ | ❌ |
| `manage_users` | Gestionar usuarios | ✅ | ❌ | ❌ |
| `manage_settings` | Configuración | ✅ | ❌ | ❌ |
| `view_analytics` | Ver estadísticas | ✅ | ✅ | ❌ |

📖 **Documentación completa**: `MODULE_2_RBAC.md`

## 📝 Códigos HTTP

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (token inválido)
- `403` - Forbidden (sin permisos)
- `404` - Not Found
- `500` - Internal Server Error

## 🔧 Troubleshooting

### "Token no proporcionado"
- Verifica que el header sea: `Authorization: Bearer <token>`
- Apache puede no pasar el header. Agrega en `.htaccess`:
  ```apache
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
  ```

### "Database connection error"
- Verifica credenciales en `.env`
- Asegúrate que la BD `sitra_web` exista

### 404 en todos los endpoints
- Verifica que `mod_rewrite` esté habilitado
- Revisa que `.htaccess` esté en `/backend/public/`

## 🚀 Próximos Módulos

- [ ] Noticias (CRUD)
- [ ] Galería (upload de imágenes)
- [ ] Documentos (PDFs)
- [ ] Directiva (miembros)
- [ ] Afiliaciones (gestión)

## 📄 Licencia

Proyecto privado - SITRACABAÑA
