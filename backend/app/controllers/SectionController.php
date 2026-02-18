<?php
/**
 * Section Controller
 * 
 * Gestiona endpoints REST para secciones CMS.
 * 
 * Endpoints protegidos (requieren JWT + edit_content):
 * - GET    /api/sections          Lista todas las secciones
 * - GET    /api/sections/{key}    Obtiene una sección específica
 * - PUT    /api/sections/{key}    Actualiza/crea una sección
 * - DELETE /api/sections/{key}    Elimina una sección
 * 
 * Endpoints públicos (sin autenticación):
 * - GET /api/public/sections      Lista todas las secciones
 * - GET /api/public/sections/{key} Obtiene una sección específica
 */

namespace App\Controllers;

use App\Services\SectionService;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Services\ActivityLogService;

class SectionController
{
    private SectionService $sectionService;
    private AuthMiddleware $authMiddleware;
    private RoleMiddleware $roleMiddleware;

    public function __construct()
    {
        $this->sectionService = new SectionService();
        $this->authMiddleware = new AuthMiddleware();
        $this->roleMiddleware = new RoleMiddleware();
    }

    // ============================================
    // ENDPOINTS PROTEGIDOS (Admin Panel)
    // ============================================

    /**
     * GET /api/sections
     * 
     * Lista todas las secciones (requiere auth + edit_content)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "section_key": "hero",
     *       "content": { "title": "..." },
     *       "updated_at": "2026-02-06 10:00:00"
     *     }
     *   ]
     * }
     */
    public function index(): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('edit_content');

            $sections = $this->sectionService->getAllSections();

            $this->successResponse($sections);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/sections/{key}
     * 
     * Obtiene una sección específica (requiere auth + edit_content)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "section_key": "hero",
     *     "content": { "title": "..." },
     *     "updated_at": "2026-02-06 10:00:00"
     *   }
     * }
     * 
     * Response (404):
     * {
     *   "success": false,
     *   "message": "Sección no encontrada"
     * }
     */
    public function show(string $key): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('edit_content');

            $section = $this->sectionService->getSectionByKey($key);

            if (!$section) {
                $this->errorResponse("Sección no encontrada", 404);
            }

            $this->successResponse($section);

        } catch (\RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/sections/{key}
     * 
     * Actualiza o crea una sección (requiere auth + edit_content)
     * 
     * Body (JSON):
     * {
     *   "content": {
     *     "title": "Mi Título",
     *     "subtitle": "Subtítulo",
     *     "items": [...]
     *   }
     * }
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "message": "Sección actualizada exitosamente",
     *   "data": {
     *     "section_key": "hero",
     *     "content": { ... },
     *     "updated_at": "2026-02-06 10:30:00"
     *   }
     * }
     */
    public function update(string $key): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('edit_content');

            $input = $this->getJsonInput();

            // Validar que se envió content
            if (!isset($input['content'])) {
                $this->errorResponse("El campo 'content' es requerido", 400);
            }

            // Actualizar sección
            $updated = $this->sectionService->updateSection($key, $input['content']);

            if (!$updated) {
                $this->errorResponse("No se pudo actualizar la sección", 500);
            }

            // Obtener sección actualizada
            $section = $this->sectionService->getSectionByKey($key);

            $userId = isset($_SERVER['AUTH_USER_ID']) ? (int) $_SERVER['AUTH_USER_ID'] : null;
            ActivityLogService::log($userId, 'UPDATE', 'section', $key, "Sección actualizada: {$key}");

            $this->successResponse($section, "Sección actualizada exitosamente");

        } catch (\RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/sections/{key}
     * 
     * Elimina una sección (requiere auth + delete_content)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "message": "Sección eliminada exitosamente"
     * }
     */
    public function delete(string $key): void
    {
        try {
            $this->authMiddleware->authenticate();
            $this->roleMiddleware->requirePermission('delete_content');

            if (!$this->sectionService->sectionExists($key)) {
                $this->errorResponse("Sección no encontrada", 404);
            }

            $deleted = $this->sectionService->deleteSection($key);

            if (!$deleted) {
                $this->errorResponse("No se pudo eliminar la sección", 500);
            }

            $userId = isset($_SERVER['AUTH_USER_ID']) ? (int) $_SERVER['AUTH_USER_ID'] : null;
            ActivityLogService::log($userId, 'DELETE', 'section', $key, "Sección eliminada: {$key}");

            $this->successResponse(null, "Sección eliminada exitosamente");

        } catch (\RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================
    // ENDPOINTS PÚBLICOS (Landing Page)
    // ============================================

    /**
     * GET /api/public/sections
     * 
     * Lista todas las secciones (público, sin autenticación)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "section_key": "hero",
     *       "content": { "title": "..." },
     *       "updated_at": "2026-02-06 10:00:00"
     *     }
     *   ]
     * }
     */
    public function publicIndex(): void
    {
        try {
            $sections = $this->sectionService->getAllSections();

            // Remover IDs para endpoints públicos
            $sections = array_map(function($section) {
                unset($section['id']);
                return $section;
            }, $sections);

            $this->successResponse($sections);

        } catch (\Exception $e) {
            $this->errorResponse("Error al obtener secciones", 500);
        }
    }

    /**
     * GET /api/public/sections/{key}
     * 
     * Obtiene una sección específica (público, sin autenticación)
     * 
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "section_key": "hero",
     *     "content": { "title": "..." },
     *     "updated_at": "2026-02-06 10:00:00"
     *   }
     * }
     */
    public function publicShow(string $key): void
    {
        try {
            $section = $this->sectionService->getSectionByKey($key);

            if (!$section) {
                $this->errorResponse("Sección no encontrada", 404);
            }

            // Remover ID para endpoints públicos
            unset($section['id']);

            $this->successResponse($section);

        } catch (\Exception $e) {
            $this->errorResponse("Error al obtener sección", 500);
        }
    }

    // ============================================
    // HELPERS
    // ============================================

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
