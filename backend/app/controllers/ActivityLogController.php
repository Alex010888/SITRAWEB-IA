<?php
/**
 * Activity Log Controller
 *
 * GET /api/logs — Lista logs de auditoría (solo lectura).
 * Requiere JWT + permiso view_logs (superadmin / directivo).
 * Paginación: ?page=1&per_page=20
 * Filtros: ?entity=section&user_id=1
 */

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Middlewares\RoleMiddleware;

class ActivityLogController
{
    private RoleMiddleware $roleMiddleware;

    public function __construct()
    {
        $this->roleMiddleware = new RoleMiddleware();
    }

    /**
     * GET /api/logs
     *
     * Query: page (int), per_page (int), entity (string), user_id (int)
     */
    public function index(): void
    {
        try {
            $this->roleMiddleware->requirePermission('view_logs');
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 403);
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $entity = isset($_GET['entity']) && is_string($_GET['entity']) ? trim($_GET['entity']) : null;
        if ($entity === '') {
            $entity = null;
        }
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        if ($userId <= 0) {
            $userId = null;
        }

        try {
            $model = new ActivityLog();
            $result = $model->getPaginated($page, $perPage, $entity, $userId);
            $this->successResponse($result);
        } catch (\Exception $e) {
            $this->errorResponse('Error al obtener logs', 500);
        }
    }

    private function successResponse(mixed $data = null, string $message = 'OK', int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function errorResponse(string $message, int $code = 400): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
