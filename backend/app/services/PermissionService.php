<?php
/**
 * Permission Service
 * 
 * Centraliza las reglas de permisos del sistema.
 * Mapea roles a permisos de manera explícita y extensible.
 */

namespace App\Services;

class PermissionService
{
    /**
     * Mapa de roles -> permisos
     * 
     * Define qué permisos tiene cada rol.
     * Los permisos son strings descriptivos (no magic strings en código).
     */
    private const PERMISSIONS_MAP = [
        'superadmin' => [
            'view_content',
            'edit_content',
            'delete_content',
            'manage_users',
            'manage_settings',
            'view_analytics',
            'view_logs',
        ],
        'directivo' => [
            'view_content',
            'edit_content',
            'view_analytics',
            'view_logs',
        ],
        'editor' => [
            'edit_content',
        ],
    ];

    /**
     * Verifica si un rol tiene un permiso específico
     * 
     * @param string $role Rol del usuario (de JWT)
     * @param string $permission Permiso requerido
     * @return bool True si tiene el permiso
     */
    public function can(string $role, string $permission): bool
    {
        // superadmin tiene todos los permisos por defecto
        if ($role === 'superadmin') {
            return true;
        }

        // Obtener permisos del rol
        $permissions = self::PERMISSIONS_MAP[$role] ?? [];

        return in_array($permission, $permissions, true);
    }

    /**
     * Verifica si un rol tiene AL MENOS UNO de los permisos dados
     * 
     * @param string $role
     * @param array $permissions Lista de permisos
     * @return bool True si tiene al menos uno
     */
    public function hasAny(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si un rol tiene TODOS los permisos dados
     * 
     * @param string $role
     * @param array $permissions Lista de permisos
     * @return bool True si tiene todos
     */
    public function hasAll(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($role, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene todos los permisos de un rol
     * 
     * @param string $role
     * @return array Lista de permisos
     */
    public function getPermissions(string $role): array
    {
        // superadmin tiene acceso a todo
        if ($role === 'superadmin') {
            return $this->getAllPermissions();
        }

        return self::PERMISSIONS_MAP[$role] ?? [];
    }

    /**
     * Obtiene todos los permisos del sistema
     * 
     * @return array Lista de todos los permisos posibles
     */
    public function getAllPermissions(): array
    {
        $all = [];
        foreach (self::PERMISSIONS_MAP as $permissions) {
            $all = array_merge($all, $permissions);
        }

        return array_unique($all);
    }

    /**
     * Obtiene todos los roles del sistema
     * 
     * @return array Lista de roles disponibles
     */
    public function getAllRoles(): array
    {
        return array_keys(self::PERMISSIONS_MAP);
    }

    /**
     * Verifica si un rol existe en el sistema
     * 
     * @param string $role
     * @return bool
     */
    public function roleExists(string $role): bool
    {
        return isset(self::PERMISSIONS_MAP[$role]);
    }
}
