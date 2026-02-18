<?php
/**
 * Role Middleware
 * 
 * Valida permisos basados en roles (RBAC).
 * Se usa DESPUÉS de AuthMiddleware para verificar permisos específicos.
 */

namespace App\Middlewares;

use App\Services\PermissionService;

class RoleMiddleware
{
    private AuthMiddleware $authMiddleware;
    private PermissionService $permissionService;

    public function __construct()
    {
        $this->authMiddleware = new AuthMiddleware();
        $this->permissionService = new PermissionService();
    }

    /**
     * Verifica que el usuario autenticado tenga un permiso específico
     * 
     * Uso en rutas:
     * (new RoleMiddleware())->requirePermission('edit_content');
     * 
     * @param string $permission Permiso requerido
     * @throws \RuntimeException Si no tiene el permiso
     */
    public function requirePermission(string $permission): void
    {
        // Primero autenticar (valida JWT)
        $this->authMiddleware->authenticate();

        // Obtener rol del usuario autenticado
        $role = $_SERVER['AUTH_USER_ROL'] ?? null;

        if (!$role) {
            $this->forbidden("Rol no encontrado en token");
        }

        // Verificar permiso
        if (!$this->permissionService->can($role, $permission)) {
            $this->forbidden("No tienes permiso para: {$permission}");
        }
    }

    /**
     * Verifica que el usuario tenga AL MENOS UNO de los permisos dados
     * 
     * @param array $permissions Lista de permisos
     * @throws \RuntimeException Si no tiene ninguno
     */
    public function requireAny(array $permissions): void
    {
        $this->authMiddleware->authenticate();

        $role = $_SERVER['AUTH_USER_ROL'] ?? null;

        if (!$role) {
            $this->forbidden("Rol no encontrado en token");
        }

        if (!$this->permissionService->hasAny($role, $permissions)) {
            $permsList = implode(', ', $permissions);
            $this->forbidden("Necesitas uno de estos permisos: {$permsList}");
        }
    }

    /**
     * Verifica que el usuario tenga TODOS los permisos dados
     * 
     * @param array $permissions Lista de permisos
     * @throws \RuntimeException Si no tiene todos
     */
    public function requireAll(array $permissions): void
    {
        $this->authMiddleware->authenticate();

        $role = $_SERVER['AUTH_USER_ROL'] ?? null;

        if (!$role) {
            $this->forbidden("Rol no encontrado en token");
        }

        if (!$this->permissionService->hasAll($role, $permissions)) {
            $permsList = implode(', ', $permissions);
            $this->forbidden("Necesitas todos estos permisos: {$permsList}");
        }
    }

    /**
     * Verifica que el usuario tenga un rol específico (legacy, usar permisos es mejor)
     * 
     * @param string|array $allowedRoles
     * @throws \RuntimeException Si no tiene el rol
     */
    public function requireRole(string|array $allowedRoles): void
    {
        $this->authMiddleware->authenticate();

        $role = $_SERVER['AUTH_USER_ROL'] ?? null;

        if (!$role) {
            $this->forbidden("Rol no encontrado en token");
        }

        $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

        if (!in_array($role, $allowedRoles, true)) {
            $rolesList = implode(', ', $allowedRoles);
            $this->forbidden("Necesitas uno de estos roles: {$rolesList}");
        }
    }

    /**
     * Factory method: crear middleware que requiere un permiso
     * 
     * Uso en rutas:
     * RoleMiddleware::require('edit_content')
     * 
     * @param string $permission
     * @return callable
     */
    public static function require(string $permission): callable
    {
        return function() use ($permission) {
            (new self())->requirePermission($permission);
        };
    }

    /**
     * Factory method: crear middleware que requiere varios permisos (ANY)
     * 
     * @param array $permissions
     * @return callable
     */
    public static function requireAnyOf(array $permissions): callable
    {
        return function() use ($permissions) {
            (new self())->requireAny($permissions);
        };
    }

    /**
     * Factory method: crear middleware que requiere varios permisos (ALL)
     * 
     * @param array $permissions
     * @return callable
     */
    public static function requireAllOf(array $permissions): callable
    {
        return function() use ($permissions) {
            (new self())->requireAll($permissions);
        };
    }

    /**
     * Responde con 403 Forbidden
     */
    private function forbidden(string $message): never
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error' => 'forbidden'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
