<?php
/**
 * API Routes
 * 
 * Definición de rutas REST para la API
 */

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\PermissionsController;
use App\Controllers\SectionController;
use App\Controllers\MediaController;
use App\Controllers\ActivityLogController;

// Router simple
class Router
{
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getPath();
    }

    private function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Remover prefijos comunes (backend/public o solo /api)
        $uri = preg_replace('#^/sitra_web/backend/public#', '', $uri);
        $uri = preg_replace('#^/backend/public#', '', $uri);
        
        return $uri ?: '/';
    }

    public function dispatch(): void
    {
        // Configurar CORS para desarrollo
        $this->handleCors();

        // ============================================
        // RUTAS PÚBLICAS (sin autenticación)
        // ============================================
        
        // POST /api/auth/login
        if ($this->method === 'POST' && $this->path === '/api/auth/login') {
            (new AuthController())->login();
            return;
        }

        // POST /api/auth/logout
        if ($this->method === 'POST' && $this->path === '/api/auth/logout') {
            (new AuthController())->logout();
            return;
        }

        // ============================================
        // RUTAS PÚBLICAS CMS (sin autenticación)
        // ============================================

        // GET /api/public/sections (lista todas las secciones - público)
        if ($this->method === 'GET' && $this->path === '/api/public/sections') {
            (new SectionController())->publicIndex();
            return;
        }

        // GET /api/public/sections/{key} (obtiene una sección - público)
        if ($this->method === 'GET' && preg_match('#^/api/public/sections/([a-zA-Z0-9_-]+)$#', $this->path, $matches)) {
            (new SectionController())->publicShow($matches[1]);
            return;
        }

        // GET /api/public/media (lista media con URLs públicas - sin auth)
        if ($this->method === 'GET' && $this->path === '/api/public/media') {
            (new MediaController())->publicIndex();
            return;
        }

        // ============================================
        // RUTAS PROTEGIDAS (requieren autenticación)
        // ============================================

        // GET /api/auth/me
        if ($this->method === 'GET' && $this->path === '/api/auth/me') {
            (new \App\Middlewares\AuthMiddleware())->authenticate();
            (new AuthController())->me();
            return;
        }

        // GET /api/permissions/me (permisos del usuario actual)
        if ($this->method === 'GET' && $this->path === '/api/permissions/me') {
            (new \App\Middlewares\AuthMiddleware())->authenticate();
            (new PermissionsController())->me();
            return;
        }

        // GET /api/permissions/roles (lista de roles y permisos - solo manage_users)
        if ($this->method === 'GET' && $this->path === '/api/permissions/roles') {
            (new \App\Middlewares\RoleMiddleware())->requirePermission('manage_users');
            (new PermissionsController())->roles();
            return;
        }

        // GET /api/users
        if ($this->method === 'GET' && $this->path === '/api/users') {
            (new UserController())->index();
            return;
        }

        // GET /api/users/{id}
        if ($this->method === 'GET' && preg_match('#^/api/users/(\d+)$#', $this->path, $matches)) {
            (new UserController())->show((int)$matches[1]);
            return;
        }

        // POST /api/users
        if ($this->method === 'POST' && $this->path === '/api/users') {
            (new UserController())->store();
            return;
        }

        // PUT /api/users/{id}
        if ($this->method === 'PUT' && preg_match('#^/api/users/(\d+)$#', $this->path, $matches)) {
            (new UserController())->update((int)$matches[1]);
            return;
        }

        // DELETE /api/users/{id}
        if ($this->method === 'DELETE' && preg_match('#^/api/users/(\d+)$#', $this->path, $matches)) {
            (new UserController())->delete((int)$matches[1]);
            return;
        }

        // ============================================
        // RUTAS PROTEGIDAS - SECTIONS (CMS Admin)
        // ============================================

        // GET /api/sections (lista todas las secciones - admin)
        if ($this->method === 'GET' && $this->path === '/api/sections') {
            (new SectionController())->index();
            return;
        }

        // GET /api/sections/{key} (obtiene una sección - admin)
        if ($this->method === 'GET' && preg_match('#^/api/sections/([a-zA-Z0-9_-]+)$#', $this->path, $matches)) {
            (new SectionController())->show($matches[1]);
            return;
        }

        // PUT /api/sections/{key} (actualiza/crea una sección - admin)
        if ($this->method === 'PUT' && preg_match('#^/api/sections/([a-zA-Z0-9_-]+)$#', $this->path, $matches)) {
            (new SectionController())->update($matches[1]);
            return;
        }

        // DELETE /api/sections/{key} (elimina una sección - admin)
        if ($this->method === 'DELETE' && preg_match('#^/api/sections/([a-zA-Z0-9_-]+)$#', $this->path, $matches)) {
            (new SectionController())->delete($matches[1]);
            return;
        }

        // ============================================
        // RUTAS PROTEGIDAS - MEDIA (Admin Panel)
        // ============================================

        // POST /api/media/upload
        if ($this->method === 'POST' && $this->path === '/api/media/upload') {
            (new MediaController())->upload();
            return;
        }

        // GET /api/media
        if ($this->method === 'GET' && $this->path === '/api/media') {
            (new MediaController())->index();
            return;
        }

        // DELETE /api/media/{id}
        if ($this->method === 'DELETE' && preg_match('#^/api/media/(\d+)$#', $this->path, $matches)) {
            (new MediaController())->delete((int) $matches[1]);
            return;
        }

        // ============================================
        // RUTAS PROTEGIDAS - ACTIVITY LOGS (view_logs)
        // ============================================

        // GET /api/logs (audit trail, paginado; ?entity=section&user_id=1&page=1&per_page=20)
        if ($this->method === 'GET' && $this->path === '/api/logs') {
            (new ActivityLogController())->index();
            return;
        }

        // ============================================
        // RUTA NO ENCONTRADA
        // ============================================
        $this->notFound();
    }

    private function handleCors(): void
    {
        $allowed = env('CORS_ALLOWED_ORIGINS', '');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($allowed !== '' && $origin !== '') {
            $list = array_map('trim', explode(',', $allowed));
            if (in_array($origin, $list, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');

        $this->setSecurityHeaders();

        if ($this->method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    private function setSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
    }

    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint no encontrado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

return new Router();
