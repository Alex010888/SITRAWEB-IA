# ============================================
# MÓDULO 2: ROLES Y PERMISOS (RBAC) - COMPLETADO ✅
# ============================================

## 📦 ARCHIVOS GENERADOS/MODIFICADOS

### NUEVOS:
- `app/services/PermissionService.php`     # Lógica centralizada de permisos
- `app/middlewares/RoleMiddleware.php`     # Middleware de validación RBAC
- `app/controllers/PermissionsController.php` # Endpoints de consulta

### MODIFICADOS:
- `app/controllers/UserController.php`     # Ahora usa permisos en vez de roles
- `routes/api.php`                         # Nuevas rutas de permisos

## 🎯 SISTEMA DE PERMISOS

### Roles disponibles:
- **superadmin** - Acceso total (todos los permisos)
- **directivo** - Gestión de contenidos + analytics
- **editor** - Solo edición de contenidos

### Permisos definidos:

| Permiso | Descripción | superadmin | directivo | editor |
|---------|-------------|------------|-----------|--------|
| `view_content` | Ver contenidos | ✅ | ✅ | ❌ |
| `edit_content` | Editar contenidos | ✅ | ✅ | ✅ |
| `delete_content` | Eliminar contenidos | ✅ | ❌ | ❌ |
| `manage_users` | Gestionar usuarios | ✅ | ❌ | ❌ |
| `manage_settings` | Configuración | ✅ | ❌ | ❌ |
| `view_analytics` | Ver estadísticas | ✅ | ✅ | ❌ |

## 🔌 NUEVOS ENDPOINTS

### GET /api/permissions/me
Obtiene los permisos del usuario autenticado

**Request:**
```http
GET /api/permissions/me
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "rol": "directivo",
    "permissions": [
      "view_content",
      "edit_content",
      "view_analytics"
    ],
    "can": {
      "view_content": true,
      "edit_content": true,
      "delete_content": false,
      "manage_users": false,
      "manage_settings": false,
      "view_analytics": true
    }
  }
}
```

### GET /api/permissions/roles
Lista todos los roles y sus permisos (solo superadmin)

**Request:**
```http
GET /api/permissions/roles
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "roles": ["superadmin", "directivo", "editor"],
    "permissions": {
      "superadmin": ["view_content", "edit_content", "delete_content", ...],
      "directivo": ["view_content", "edit_content", "view_analytics"],
      "editor": ["edit_content"]
    }
  }
}
```

## 💻 USO EN CÓDIGO

### Método 1: En controllers (recomendado)

```php
use App\Middlewares\RoleMiddleware;

class ContentController {
    private RoleMiddleware $roleMiddleware;
    
    public function update() {
        // Validar permiso específico
        $this->roleMiddleware->requirePermission('edit_content');
        
        // Lógica del endpoint...
    }
}
```

### Método 2: En rutas (más limpio)

```php
// En routes/api.php

// Requiere UN permiso
if ($this->method === 'PUT' && $this->path === '/api/content/update') {
    (new RoleMiddleware())->requirePermission('edit_content');
    (new ContentController())->update();
    return;
}

// Requiere AL MENOS UNO de varios permisos
if ($this->method === 'GET' && $this->path === '/api/dashboard') {
    (new RoleMiddleware())->requireAny(['view_content', 'view_analytics']);
    (new DashboardController())->index();
    return;
}

// Requiere TODOS los permisos
if ($this->method === 'DELETE' && $this->path === '/api/content/purge') {
    (new RoleMiddleware())->requireAll(['delete_content', 'manage_settings']);
    (new ContentController())->purge();
    return;
}
```

### Método 3: Factory (sintaxis corta)

```php
// En routes:
if ($this->method === 'PUT' && $this->path === '/api/content') {
    RoleMiddleware::require('edit_content')();
    (new ContentController())->update();
    return;
}
```

## 🛡️ RESPUESTAS DE ERROR

### 403 Forbidden (sin permiso)

```json
{
  "success": false,
  "message": "No tienes permiso para: edit_content",
  "error": "forbidden"
}
```

### 403 Forbidden (sin rol requerido)

```json
{
  "success": false,
  "message": "Necesitas uno de estos roles: superadmin, directivo",
  "error": "forbidden"
}
```

## 🧪 EJEMPLOS DE PRUEBA

### 1. Login como editor
```bash
curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"editor@example.com","password":"password123"}'
```

### 2. Obtener permisos del usuario
```bash
curl -X GET http://localhost/sitra_web/backend/public/api/permissions/me \
  -H "Authorization: Bearer <TOKEN_EDITOR>"
```

Respuesta:
```json
{
  "success": true,
  "data": {
    "rol": "editor",
    "permissions": ["edit_content"],
    "can": {
      "view_content": false,
      "edit_content": true,
      "delete_content": false,
      "manage_users": false
    }
  }
}
```

### 3. Intentar crear usuario (debe fallar)
```bash
curl -X POST http://localhost/sitra_web/backend/public/api/users \
  -H "Authorization: Bearer <TOKEN_EDITOR>" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Test","email":"test@test.com","password":"123456"}'
```

Respuesta esperada (403):
```json
{
  "success": false,
  "message": "No tienes permiso para: manage_users",
  "error": "forbidden"
}
```

## 🎨 ARQUITECTURA

```
Request → AuthMiddleware (valida JWT) 
       → RoleMiddleware (valida permiso)
       → Controller (lógica de negocio)
       → Response
```

### Flujo de validación:

1. **AuthMiddleware**: Valida JWT, extrae `user_id` y `rol`
2. **RoleMiddleware**: Lee `rol` de `$_SERVER['AUTH_USER_ROL']`
3. **PermissionService**: Consulta si ese rol tiene el permiso
4. Si no tiene → 403 Forbidden
5. Si tiene → continúa al controller

## ✅ CARACTERÍSTICAS

✅ Centralizado (un solo lugar: `PermissionService`)
✅ Extensible (agregar permisos es trivial)
✅ Type-safe (PHP 8+ strict types)
✅ Sin base de datos (permisos en código)
✅ Sin magic strings
✅ Reutiliza JWT de Módulo 1
✅ Múltiples métodos de validación (`can`, `hasAny`, `hasAll`)
✅ Factory methods para sintaxis limpia
✅ Códigos HTTP correctos

## 📝 AGREGAR NUEVOS PERMISOS

### Paso 1: Definir en PermissionService

```php
// En app/services/PermissionService.php

private const PERMISSIONS_MAP = [
    'superadmin' => [
        'view_content',
        'edit_content',
        'delete_content',
        'manage_users',
        'manage_settings',
        'view_analytics',
        'upload_images',      // <-- NUEVO
    ],
    'directivo' => [
        'view_content',
        'edit_content',
        'view_analytics',
        'upload_images',      // <-- NUEVO
    ],
    'editor' => [
        'edit_content',
        'upload_images',      // <-- NUEVO
    ],
];
```

### Paso 2: Usar en rutas

```php
// En routes/api.php

if ($this->method === 'POST' && $this->path === '/api/gallery/upload') {
    (new RoleMiddleware())->requirePermission('upload_images');
    (new GalleryController())->upload();
    return;
}
```

## 🔒 MEJORES PRÁCTICAS

### ✅ CORRECTO:

```php
// Usar permisos, no roles directos
$roleMiddleware->requirePermission('edit_content');

// Centralizar permisos en PermissionService
$permissionService->can($role, 'manage_users');
```

### ❌ INCORRECTO:

```php
// NO hardcodear roles en lógica de negocio
if ($user['rol'] === 'superadmin') {
    // ...
}

// NO usar strings mágicos dispersos
if ($role !== 'admin' && $role !== 'directivo') {
    // ...
}
```

## 🧩 INTEGRACIÓN CON FUTUROS MÓDULOS

Cuando agregues noticias, galería, etc:

```php
// Módulo Noticias
class NewsController {
    public function store() {
        (new RoleMiddleware())->requirePermission('edit_content');
        // Crear noticia...
    }
    
    public function destroy($id) {
        (new RoleMiddleware())->requirePermission('delete_content');
        // Eliminar noticia...
    }
}

// Módulo Galería
class GalleryController {
    public function upload() {
        (new RoleMiddleware())->requirePermission('edit_content');
        // Subir imagen...
    }
}
```

## 📊 MATRIZ DE PERMISOS COMPLETA

```
Permiso             | superadmin | directivo | editor
--------------------|------------|-----------|--------
view_content        |     ✅     |    ✅     |   ❌
edit_content        |     ✅     |    ✅     |   ✅
delete_content      |     ✅     |    ❌     |   ❌
manage_users        |     ✅     |    ❌     |   ❌
manage_settings     |     ✅     |    ❌     |   ❌
view_analytics      |     ✅     |    ✅     |   ❌
```

## 🚀 PRÓXIMOS PASOS

1. Implementar módulos de contenido (noticias, galería, etc.)
2. Agregar permisos específicos según necesidad
3. Opcional: Persistir permisos en BD (tabla `permissions`)
4. Opcional: Permisos por usuario (override de rol)

## 🎉 MÓDULO 2 COMPLETADO

El sistema RBAC está 100% funcional y listo para usar en futuros módulos.
