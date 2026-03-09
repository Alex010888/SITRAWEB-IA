<?php
/**
 * Auth Controller
 * 
 * Maneja login y logout
 */

namespace App\Controllers;

use App\Models\User;
use App\Services\JwtService;
use App\Services\ActivityLogService;

class AuthController
{
    private User $userModel;
    private JwtService $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtService = new JwtService();
    }

    /**
     * POST /api/auth/login
     * 
     * Login de usuario y generación de JWT
     * 
     * Body (JSON):
     * {
     *   "email": "admin@sitracabana.org",
     *   "password": "admin123"
     * }
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "message": "Login exitoso",
     *   "data": {
     *     "token": "eyJhbGciOiJIUzI1...",
     *     "user": {
     *       "id": 1,
     *       "nombre": "Administrador",
     *       "email": "admin@sitracabana.org",
     *       "rol": "superadmin"
     *     }
     *   }
     * }
     */
    public function login(): void
    {
        try {
            // Obtener input
            $input = $this->getJsonInput();

            // Validar campos requeridos
            if (empty($input['email']) || empty($input['password'])) {
                $this->errorResponse("Email y password son requeridos", 400);
            }

            // Misma normalización que al registrar: trim + minúsculas (tabla usuarios)
            $email = strtolower(trim((string) $input['email']));
            $password = $input['password'];

            // Buscar usuario en la tabla usuarios
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                $this->errorResponse("Credenciales inválidas", 401);
            }

            // Verificar si está activo
            if (!$user['activo']) {
                $this->errorResponse("Usuario inactivo", 403);
            }

            // Verificar password
            if (!password_verify($password, $user['password'])) {
                $this->errorResponse("Credenciales inválidas", 401);
            }

            // Generar JWT
            $token = $this->jwtService->encode([
                'user_id' => $user['id'],
                'rol' => $user['rol'],
            ]);

            // Preparar datos del usuario (sin password)
            $userData = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
            ];

            // Audit: login exitoso (fail silently)
            ActivityLogService::log(
                (int) $user['id'],
                'LOGIN',
                'auth',
                null,
                'Login exitoso'
            );

            // Respuesta exitosa
            $this->successResponse([
                'token' => $token,
                'user' => $userData,
            ], "Login exitoso");

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/auth/logout
     * 
     * Logout (client-side token invalidation)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "message": "Logout exitoso"
     * }
     */
    public function logout(): void
    {
        $userId = null;
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            $payload = $this->jwtService->decode(trim($m[1]));
            if ($payload && isset($payload['user_id'])) {
                $userId = (int) $payload['user_id'];
            }
        }
        ActivityLogService::log($userId, 'LOGOUT', 'auth', null, 'Logout');

        $this->successResponse(
            null,
            "Logout exitoso. Elimina el token del cliente."
        );
    }

    /**
     * GET /api/auth/me
     * 
     * Obtiene información del usuario autenticado
     * Requiere JWT válido
     */
    public function me(): void
    {
        try {
            $userId = $_SERVER['AUTH_USER_ID'] ?? null;

            if (!$userId) {
                $this->errorResponse("No autenticado", 401);
            }

            $user = $this->userModel->findById((int)$userId);

            if (!$user) {
                $this->errorResponse("Usuario no encontrado", 404);
            }

            $this->successResponse($user);

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
