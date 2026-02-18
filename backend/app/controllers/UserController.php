<?php
/**
 * User Controller
 * 
 * CRUD de usuarios (protegido por autenticación)
 */

namespace App\Controllers;

use App\Models\User;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;

class UserController
{
    private User $userModel;
    private AuthMiddleware $authMiddleware;
    private RoleMiddleware $roleMiddleware;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authMiddleware = new AuthMiddleware();
        $this->roleMiddleware = new RoleMiddleware();
    }

    /**
     * GET /api/users
     * 
     * Lista todos los usuarios (sin passwords)
     * Requiere autenticación
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nombre": "Administrador",
     *       "email": "admin@sitracabana.org",
     *       "rol": "superadmin",
     *       "activo": 1,
     *       "created_at": "2026-02-06 20:00:00"
     *     }
     *   ]
     * }
     */
    public function index(): void
    {
        try {
            // Verificar autenticación
            $this->authMiddleware->authenticate();

            $users = $this->userModel->getAll();

            $this->successResponse($users);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/users/{id}
     * 
     * Obtiene un usuario por ID
     * Requiere autenticación
     */
    public function show(int $id): void
    {
        try {
            $this->authMiddleware->authenticate();

            $user = $this->userModel->findById($id);

            if (!$user) {
                $this->errorResponse("Usuario no encontrado", 404);
            }

            $this->successResponse($user);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/users
     * 
     * Crea un nuevo usuario
     * Requiere autenticación y rol superadmin
     * 
     * Body (JSON):
     * {
     *   "nombre": "Juan Pérez",
     *   "email": "juan@example.com",
     *   "password": "secreto123",
     *   "rol": "editor",
     *   "activo": 1
     * }
     * 
     * Response (201):
     * {
     *   "success": true,
     *   "message": "Usuario creado exitosamente",
     *   "data": {
     *     "id": 2,
     *     "nombre": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "rol": "editor",
     *     "activo": 1
     *   }
     * }
     */
    public function store(): void
    {
        try {
            // Verificar autenticación y permiso manage_users
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('manage_users');

            // Obtener input
            $input = $this->getJsonInput();

            // Crear usuario
            $userId = $this->userModel->create($input);

            // Obtener usuario creado
            $user = $this->userModel->findById($userId);

            $currentUserId = $_SERVER['AUTH_USER_ID'] ?? null;
            ActivityLogService::log(
                $currentUserId ? (int) $currentUserId : null,
                'CREATE',
                'user',
                (string) $userId,
                'Usuario creado: ' . ($user['email'] ?? '')
            );

            $this->successResponse($user, "Usuario creado exitosamente", 201);

        } catch (\RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/users/{id}
     * 
     * Actualiza un usuario
     * Requiere autenticación y rol superadmin
     * 
     * Body (JSON):
     * {
     *   "nombre": "Juan Actualizado",
     *   "rol": "directivo"
     * }
     */
    public function update(int $id): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('manage_users');

            $input = $this->getJsonInput();

            $updated = $this->userModel->update($id, $input);

            if (!$updated) {
                $this->errorResponse("No se pudo actualizar el usuario", 400);
            }

            $user = $this->userModel->findById($id);

            $this->successResponse($user, "Usuario actualizado exitosamente");

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/users/{id}
     * 
     * Desactiva un usuario (soft delete)
     * Requiere autenticación y rol superadmin
     */
    public function delete(int $id): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('manage_users');

            // No permitir eliminar al propio usuario
            $currentUserId = $_SERVER['AUTH_USER_ID'] ?? null;
            if ($currentUserId == $id) {
                $this->errorResponse("No puedes desactivar tu propia cuenta", 400);
            }

            $deleted = $this->userModel->delete($id);

            if (!$deleted) {
                $this->errorResponse("No se pudo desactivar el usuario", 400);
            }

            $currentUserId = $_SERVER['AUTH_USER_ID'] ?? null;
            ActivityLogService::log(
                $currentUserId ? (int) $currentUserId : null,
                'DELETE',
                'user',
                (string) $id,
                'Usuario desactivado'
            );

            $this->successResponse(null, "Usuario desactivado exitosamente");

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el input JSON del body
     */
    private function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->errorResponse("JSON inválido", 400);
        }

        return $data;
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
