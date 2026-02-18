# Módulo 6: Activity Logs / Audit Trail

Sistema de auditoría de solo lectura que registra acciones críticas del panel admin.

## Instalación

1. Ejecutar el SQL de la tabla `activity_logs`:
   ```bash
   mysql -u root -p sitra_web < database_activity_logs.sql
   ```

2. El permiso `view_logs` ya está asignado a **superadmin** y **directivo** en `PermissionService`. No requiere cambios en BD de permisos (los permisos se definen en código).

## Endpoint

### GET /api/logs (protegido)

- **Autenticación:** JWT obligatorio.
- **Permiso:** `view_logs` (superadmin, directivo).

**Query params:**

| Parámetro  | Tipo   | Descripción                          |
|-----------|--------|--------------------------------------|
| `page`    | int    | Página (por defecto 1)               |
| `per_page`| int    | Registros por página (1–100, default 20) |
| `entity`  | string | Filtrar por entidad: section, media, user, auth |
| `user_id` | int    | Filtrar por usuario que realizó la acción |

**Ejemplos de solicitud:**

```http
GET /api/logs
GET /api/logs?entity=section
GET /api/logs?user_id=1
GET /api/logs?page=2&per_page=10
GET /api/logs?entity=media&page=1&per_page=20
```

**Ejemplo de respuesta (200):**

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": 1,
        "action": "LOGIN",
        "entity": "auth",
        "entity_id": null,
        "description": "Login exitoso",
        "ip_address": "127.0.0.1",
        "created_at": "2026-02-08 14:30:00"
      },
      {
        "id": 2,
        "user_id": 1,
        "action": "UPDATE",
        "entity": "section",
        "entity_id": "hero",
        "description": "Sección actualizada: hero",
        "ip_address": "127.0.0.1",
        "created_at": "2026-02-08 14:32:00"
      }
    ],
    "total": 42,
    "page": 1,
    "per_page": 20
  }
}
```

## Acciones registradas

| Acción  | Entidad | Cuándo                    |
|---------|---------|---------------------------|
| LOGIN   | auth    | Login exitoso             |
| LOGOUT  | auth    | Logout                    |
| UPDATE  | section | Sección CMS actualizada   |
| DELETE  | section | Sección CMS eliminada     |
| UPLOAD  | media   | Archivo subido            |
| DELETE  | media   | Archivo eliminado         |
| CREATE  | user    | Usuario creado            |
| UPDATE  | user    | Usuario actualizado       |
| DELETE  | user    | Usuario desactivado       |

## Creación de logs desde código

El servicio **no expone** creación de logs por API (solo lectura). Los logs se crean desde controladores tras una acción exitosa:

```php
use App\Services\ActivityLogService;

// Tras actualizar una sección
ActivityLogService::log(
    (int) $_SERVER['AUTH_USER_ID'],
    'UPDATE',
    'section',
    $sectionKey,
    "Sección actualizada: {$sectionKey}"
);

// Tras subir un archivo
ActivityLogService::log(
    $userId,
    'UPLOAD',
    'media',
    (string) $record['id'],
    'Archivo subido: ' . $record['original_name']
);

// Login (user_id del usuario que inicia sesión)
ActivityLogService::log($user['id'], 'LOGIN', 'auth', null, 'Login exitoso');

// Logout (user_id puede ser null si no hay token)
ActivityLogService::log($userId, 'LOGOUT', 'auth', null, 'Logout');
```

- **Fail silently:** Si la inserción del log falla, no se lanza excepción y el flujo principal sigue.
- **Sin datos sensibles:** No se almacenan contraseñas ni tokens.

## Paginación

- `per_page` está limitado entre 1 y 100.
- `total` es el número total de registros que cumplen el filtro.
- Los resultados se ordenan por `created_at DESC`.

## Seguridad

- Los logs son **solo lectura** vía API: no hay PUT, PATCH ni DELETE.
- Solo usuarios con permiso `view_logs` pueden listar logs.
- No se guardan datos sensibles (solo descripciones y metadatos de auditoría).
