# 🚀 QUICK START - Sistema RBAC

Guía rápida para usar el sistema de permisos en 2 minutos.

## 📌 Lo Básico

**3 roles disponibles:**
- `superadmin` - Todo permitido
- `directivo` - Ver y editar contenidos + analytics
- `editor` - Solo editar contenidos

**6 permisos definidos:**
```php
'view_content'     // Ver contenidos
'edit_content'     // Editar contenidos
'delete_content'   // Eliminar contenidos
'manage_users'     // Gestionar usuarios
'manage_settings'  // Configuración
'view_analytics'   // Ver estadísticas
```

## 💻 Uso en Controllers

```php
use App\Middlewares\RoleMiddleware;

class MyController {
    private RoleMiddleware $roleMiddleware;
    
    public function __construct() {
        $this->roleMiddleware = new RoleMiddleware();
    }
    
    public function update() {
        // Requiere permiso específico
        $this->roleMiddleware->requirePermission('edit_content');
        
        // Tu código aquí...
    }
}
```

## 🛣️ Uso en Rutas

```php
// En routes/api.php

// Opción 1: Instancia directa
if ($this->method === 'PUT' && $this->path === '/api/noticias') {
    (new RoleMiddleware())->requirePermission('edit_content');
    (new NoticiasController())->update();
    return;
}

// Opción 2: Factory method (más limpio)
if ($this->method === 'PUT' && $this->path === '/api/noticias') {
    RoleMiddleware::require('edit_content')();
    (new NoticiasController())->update();
    return;
}
```

## 🧩 Métodos Disponibles

```php
// Requiere UN permiso
$roleMiddleware->requirePermission('edit_content');

// Requiere AL MENOS UNO de varios
$roleMiddleware->requireAny(['view_content', 'edit_content']);

// Requiere TODOS
$roleMiddleware->requireAll(['edit_content', 'manage_settings']);

// Requiere rol específico (legacy, mejor usar permisos)
$roleMiddleware->requireRole('superadmin');
$roleMiddleware->requireRole(['superadmin', 'directivo']);
```

## 📝 Agregar Nuevo Permiso

**Paso 1:** Edita `app/services/PermissionService.php`

```php
private const PERMISSIONS_MAP = [
    'superadmin' => [
        // ... permisos existentes ...
        'upload_images',  // <-- NUEVO
    ],
    'directivo' => [
        // ... permisos existentes ...
        'upload_images',  // <-- NUEVO
    ],
    'editor' => [
        'edit_content',
    ],
];
```

**Paso 2:** Úsalo en tu código

```php
if ($this->method === 'POST' && $this->path === '/api/gallery/upload') {
    RoleMiddleware::require('upload_images')();
    (new GalleryController())->upload();
    return;
}
```

¡Listo! 🎉

## 🔍 Consultar Permisos desde Frontend

```bash
# Obtener permisos del usuario actual
GET /api/permissions/me
Authorization: Bearer <TOKEN>

# Response:
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

Úsalo en tu frontend para:
- Mostrar/ocultar botones
- Habilitar/deshabilitar acciones
- Personalizar la UI según permisos

## 🎯 Respuestas HTTP

- **200 OK** - Permiso válido, acción ejecutada
- **401 Unauthorized** - Token inválido/expirado
- **403 Forbidden** - Sin permiso para esta acción

```json
{
  "success": false,
  "message": "No tienes permiso para: manage_users",
  "error": "forbidden"
}
```

## 📚 Más Info

- Documentación completa: `MODULE_2_RBAC.md`
- Arquitectura: `ARCHITECTURE_RBAC.md`
- Ejemplos cURL: `EXAMPLES_RBAC.sh`
- Tests: `php backend/test_rbac.php`

---

**¿Dudas?** Lee `MODULE_2_RBAC.md` para detalles completos.
