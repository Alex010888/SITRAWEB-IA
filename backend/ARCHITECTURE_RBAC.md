# ============================================
# ARQUITECTURA DEL SISTEMA RBAC
# ============================================

## FLUJO DE REQUEST CON PERMISOS

```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT (Frontend)                      │
│                                                             │
│  Request: PUT /api/users/2                                  │
│  Headers:                                                   │
│    Authorization: Bearer eyJhbGci...                        │
│    Content-Type: application/json                           │
│  Body: { "nombre": "Juan Updated" }                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              BACKEND: public/index.php                      │
│                                                             │
│  1. Autoload classes                                        │
│  2. Load .env variables                                     │
│  3. Dispatch router                                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                 ROUTER: routes/api.php                      │
│                                                             │
│  Match: PUT /api/users/{id}                                 │
│                                                             │
│  if ($method === 'PUT' && preg_match(...)) {                │
│    (new UserController())->update($id);                     │
│  }                                                          │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│        CONTROLLER: UserController->update()                 │
│                                                             │
│  1. Verificar autenticación                                 │
│     → AuthMiddleware->authenticate()                        │
│                                                             │
│  2. Verificar permiso                                       │
│     → RoleMiddleware->requirePermission('manage_users')     │
│                                                             │
│  3. Si pasa, ejecutar lógica                                │
│     → $userModel->update($id, $data)                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
         ┌────────────┴────────────┐
         ▼                         ▼
┌──────────────────┐    ┌──────────────────────┐
│ AuthMiddleware   │    │   RoleMiddleware     │
│                  │    │                      │
│ 1. Lee header    │    │ 1. Lee $_SERVER[     │
│    Authorization │    │    'AUTH_USER_ROL']  │
│                  │    │                      │
│ 2. Valida JWT    │    │ 2. Consulta          │
│    (JwtService)  │    │    PermissionService │
│                  │    │                      │
│ 3. Inyecta datos │    │ 3. Si NO tiene       │
│    $_SERVER[     │    │    permiso → 403     │
│     AUTH_USER_ID │    │                      │
│     AUTH_USER_ROL│    │ 4. Si tiene → OK     │
│    ]             │    │    (continúa)        │
│                  │    │                      │
│ 4. Si válido     │    └──────────┬───────────┘
│    → OK          │               │
│    Si no → 401   │               │
└────────┬─────────┘               │
         │                         │
         └─────────┬───────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│          SERVICE: PermissionService->can()                  │
│                                                             │
│  const PERMISSIONS_MAP = [                                  │
│    'superadmin' => ['view_content', 'edit_content', ...],   │
│    'directivo'  => ['view_content', 'edit_content'],        │
│    'editor'     => ['edit_content'],                        │
│  ];                                                         │
│                                                             │
│  can('directivo', 'manage_users')                           │
│    → false (no está en su array)                            │
│                                                             │
│  can('superadmin', 'manage_users')                          │
│    → true (superadmin puede todo)                           │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                     RESPONSE (JSON)                         │
│                                                             │
│  Success (200):                                             │
│  {                                                          │
│    "success": true,                                         │
│    "message": "Usuario actualizado",                        │
│    "data": { ... }                                          │
│  }                                                          │
│                                                             │
│  Forbidden (403):                                           │
│  {                                                          │
│    "success": false,                                        │
│    "message": "No tienes permiso para: manage_users",       │
│    "error": "forbidden"                                     │
│  }                                                          │
│                                                             │
│  Unauthorized (401):                                        │
│  {                                                          │
│    "success": false,                                        │
│    "message": "Token inválido o expirado"                   │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
```

## JERARQUÍA DE CLASES

```
┌─────────────────────────────────────────┐
│          PermissionService              │
│                                         │
│  - PERMISSIONS_MAP (const)              │
│  + can(role, permission): bool          │
│  + hasAny(role, permissions[]): bool    │
│  + hasAll(role, permissions[]): bool    │
│  + getPermissions(role): array          │
└────────────────┬────────────────────────┘
                 │
                 │ usado por
                 ▼
┌─────────────────────────────────────────┐
│           RoleMiddleware                │
│                                         │
│  - authMiddleware: AuthMiddleware       │
│  - permissionService: PermissionService │
│                                         │
│  + requirePermission(permission)        │
│  + requireAny(permissions[])            │
│  + requireAll(permissions[])            │
│  + requireRole(roles[])                 │
│                                         │
│  # Factory methods:                     │
│  + static require(perm): callable       │
│  + static requireAnyOf(perms): callable │
│  + static requireAllOf(perms): callable │
└────────────────┬────────────────────────┘
                 │
                 │ usa
                 ▼
┌─────────────────────────────────────────┐
│           AuthMiddleware                │
│                                         │
│  - jwtService: JwtService               │
│                                         │
│  + authenticate(): void                 │
│  + requireRole(role): void              │
│                                         │
│  Inyecta en $_SERVER:                   │
│    - AUTH_USER_ID                       │
│    - AUTH_USER_ROL                      │
└─────────────────────────────────────────┘
```

## MATRIZ DE PERMISOS

```
┌─────────────────┬────────────┬───────────┬─────────┐
│     PERMISO     │ superadmin │ directivo │ editor  │
├─────────────────┼────────────┼───────────┼─────────┤
│ view_content    │     ✅     │    ✅     │   ❌    │
│ edit_content    │     ✅     │    ✅     │   ✅    │
│ delete_content  │     ✅     │    ❌     │   ❌    │
│ manage_users    │     ✅     │    ❌     │   ❌    │
│ manage_settings │     ✅     │    ❌     │   ❌    │
│ view_analytics  │     ✅     │    ✅     │   ❌    │
└─────────────────┴────────────┴───────────┴─────────┘
```

## ENDPOINTS Y PERMISOS

```
┌──────────────────────────────┬─────────────────┬──────────────┐
│          ENDPOINT            │    MÉTODO       │   PERMISO    │
├──────────────────────────────┼─────────────────┼──────────────┤
│ /api/auth/login              │ POST            │ público      │
│ /api/auth/logout             │ POST            │ público      │
│ /api/auth/me                 │ GET             │ autenticado  │
│ /api/permissions/me          │ GET             │ autenticado  │
│ /api/permissions/roles       │ GET             │ manage_users │
│ /api/users                   │ GET             │ autenticado  │
│ /api/users/{id}              │ GET             │ autenticado  │
│ /api/users                   │ POST            │ manage_users │
│ /api/users/{id}              │ PUT             │ manage_users │
│ /api/users/{id}              │ DELETE          │ manage_users │
└──────────────────────────────┴─────────────────┴──────────────┘
```

## DECISIONES DE DISEÑO

### ✅ Por qué permisos en código (no en BD):

1. **Simplicidad**: No requiere queries adicionales
2. **Performance**: Zero latency (array en memoria)
3. **Type-safe**: PHP valida en tiempo de compilación
4. **Versionado**: Los permisos están en Git
5. **Despliegue simple**: No requiere migrations

### ✅ Por qué centralizar en PermissionService:

1. **Single Source of Truth**: Un solo lugar define permisos
2. **DRY**: No repetir lógica en controllers
3. **Testeable**: Se puede unit-test fácilmente
4. **Extensible**: Agregar permisos es trivial

### ✅ Por qué superadmin tiene acceso directo:

```php
if ($role === 'superadmin') {
    return true; // bypass lista de permisos
}
```

- Evita mantener lista gigante de permisos
- Garantiza que siempre tiene acceso total
- Facilita agregar nuevos permisos sin actualizar superadmin

### ✅ Por qué usar Factory methods:

```php
// Sintaxis limpia en rutas:
RoleMiddleware::require('edit_content')();

// vs sintaxis verbosa:
(new RoleMiddleware())->requirePermission('edit_content');
```

## EXTENSIÓN FUTURA

### Permisos por usuario (override de rol):

```sql
CREATE TABLE user_permissions (
  user_id INT,
  permission VARCHAR(50),
  granted BOOLEAN
);
```

```php
// En PermissionService:
public function can(string $role, string $permission, ?int $userId = null): bool {
    // 1. Verificar override por usuario
    if ($userId) {
        $override = $this->getUserPermissionOverride($userId, $permission);
        if ($override !== null) return $override;
    }
    
    // 2. Verificar permiso de rol (lógica actual)
    return in_array($permission, self::PERMISSIONS_MAP[$role] ?? []);
}
```

### Permisos dinámicos desde BD:

```php
private function getRolePermissions(string $role): array {
    static $cache = [];
    
    if (!isset($cache[$role])) {
        $cache[$role] = $this->db->query(
            "SELECT permission FROM role_permissions WHERE role = ?",
            [$role]
        )->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return $cache[$role];
}
```

## RESUMEN

✅ Sistema RBAC completamente funcional
✅ Integrado con JWT de Módulo 1
✅ Sin cambios en base de datos
✅ Fácil de extender
✅ Performance óptimo
✅ Código limpio y mantenible
