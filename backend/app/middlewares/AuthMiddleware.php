<?php
/**
 * Auth Middleware
 * 
 * Valida JWT en cada request protegido.
 * Inyecta información del usuario autenticado en $_SERVER.
 */

namespace App\Middlewares;

use App\Services\JwtService;

class AuthMiddleware
{
    private JwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    /**
     * Valida el token JWT del header Authorization
     * 
     * @return array Payload del JWT (user_id, rol, etc.)
     * @throws \RuntimeException Si el token es inválido o falta
     */
    public function authenticate(): array
    {
        // Obtener header Authorization
        $authHeader = $this->getAuthorizationHeader();

        if (!$authHeader) {
            $this->unauthorized("Token no proporcionado");
        }

        // Validar formato: "Bearer <token>"
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $this->unauthorized("Formato de token inválido");
        }

        $token = $matches[1];

        // Decodificar y validar JWT
        $payload = $this->jwtService->decode($token);

        if (!$payload) {
            $this->unauthorized("Token inválido o expirado");
        }

        // Validar campos requeridos
        if (!isset($payload['user_id']) || !isset($payload['rol'])) {
            $this->unauthorized("Token malformado");
        }

        // Inyectar datos del usuario en $_SERVER para uso posterior
        $_SERVER['AUTH_USER_ID'] = $payload['user_id'];
        $_SERVER['AUTH_USER_ROL'] = $payload['rol'];

        return $payload;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param string|array $requiredRoles
     * @throws \RuntimeException Si no tiene el rol
     */
    public function requireRole(string|array $requiredRoles): void
    {
        if (!isset($_SERVER['AUTH_USER_ROL'])) {
            $this->forbidden("No autenticado");
        }

        $requiredRoles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
        $userRole = $_SERVER['AUTH_USER_ROL'];

        if (!in_array($userRole, $requiredRoles, true)) {
            $this->forbidden("No tienes permisos suficientes");
        }
    }

    /**
     * Obtiene el header Authorization
     * 
     * @return string|null
     */
    private function getAuthorizationHeader(): ?string
    {
        // Apache y mod_php
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Nginx
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Usando apache_request_headers() si está disponible
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
        }

        return null;
    }

    /**
     * Responde con 401 Unauthorized
     */
    private function unauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
