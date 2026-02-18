<?php
/**
 * Permissions Controller
 * 
 * Endpoint para que el frontend consulte permisos del usuario actual
 */

namespace App\Controllers;

use App\Services\PermissionService;

class PermissionsController
{
    private PermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * GET /api/permissions/me
     * 
     * Obtiene los permisos del usuario autenticado
     * Requiere autenticación
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "rol": "directivo",
     *     "permissions": [
     *       "view_content",
     *       "edit_content",
     *       "view_analytics"
     *     ],
     *     "can": {
     *       "view_content": true,
     *       "edit_content": true,
     *       "delete_content": false,
     *       "manage_users": false
     *     }
     *   }
     * }
     */
    public function me(): void
    {
        try {
            $role = $_SERVER['AUTH_USER_ROL'] ?? null;

            if (!$role) {
                $this->errorResponse("No autenticado", 401);
            }

            $permissions = $this->permissionService->getPermissions($role);

            // Matriz de permisos comunes (para el frontend)
            $allPermissions = $this->permissionService->getAllPermissions();
            $canMap = [];
            foreach ($allPermissions as $perm) {
                $canMap[$perm] = $this->permissionService->can($role, $perm);
            }

            $this->successResponse([
                'rol' => $role,
                'permissions' => $permissions,
                'can' => $canMap,
            ]);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/permissions/roles
     * 
     * Lista todos los roles y sus permisos
     * Requiere autenticación + permiso manage_users
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "roles": ["superadmin", "directivo", "editor"],
     *     "permissions": {
     *       "superadmin": ["view_content", "edit_content", ...],
     *       "directivo": ["view_content", "edit_content"],
     *       "editor": ["edit_content"]
     *     }
     *   }
     * }
     */
    public function roles(): void
    {
        try {
            $roles = $this->permissionService->getAllRoles();
            $permissionsMap = [];

            foreach ($roles as $role) {
                $permissionsMap[$role] = $this->permissionService->getPermissions($role);
            }

            $this->successResponse([
                'roles' => $roles,
                'permissions' => $permissionsMap,
            ]);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Respuesta de éxito
     */
    private function successResponse(mixed $data = null, string $message = "OK", int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Respuesta de error
     */
    private function errorResponse(string $message, int $code = 400): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
