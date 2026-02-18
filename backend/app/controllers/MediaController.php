<?php
/**
 * Media Controller
 *
 * Endpoints REST para gestión de media (imágenes y PDFs).
 *
 * Protegidos (JWT + edit_content):
 * - POST   /api/media/upload
 * - GET    /api/media
 * - DELETE /api/media/{id}
 *
 * Público:
 * - GET    /api/public/media
 */

namespace App\Controllers;

use App\Services\MediaService;
use App\Middlewares\RoleMiddleware;
use App\Services\ActivityLogService;

class MediaController
{
    private MediaService $mediaService;
    private RoleMiddleware $roleMiddleware;

    public function __construct()
    {
        $this->mediaService = new MediaService();
        $this->roleMiddleware = new RoleMiddleware();
    }

    /**
     * POST /api/media/upload
     *
     * Multipart: field "file" (archivo) y opcional "section_key".
     * 400: archivo inválido o tipo no permitido.
     * 403: sin permiso edit_content.
     */
    public function upload(): void
    {
        try {
            $this->roleMiddleware->requirePermission('edit_content');
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 403);
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || !isset($file['tmp_name'])) {
            $this->errorResponse('Campo "file" es requerido (multipart/form-data)', 400);
        }

        $sectionKey = isset($_POST['section_key']) && is_string($_POST['section_key'])
            ? trim($_POST['section_key'])
            : null;
        if ($sectionKey === '') {
            $sectionKey = null;
        }

        try {
            $record = $this->mediaService->upload($file, $sectionKey);
            $userId = isset($_SERVER['AUTH_USER_ID']) ? (int) $_SERVER['AUTH_USER_ID'] : null;
            ActivityLogService::log(
                $userId,
                'UPLOAD',
                'media',
                (string) $record['id'],
                'Archivo subido: ' . ($record['original_name'] ?? '')
            );
            $this->successResponse($record, 'Archivo subido correctamente', 201);
        } catch (\RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse('Error al subir el archivo', 500);
        }
    }

    /**
     * GET /api/media
     *
     * Query opcional: section_key (filtrar por sección).
     * 403: sin permiso edit_content.
     */
    public function index(): void
    {
        try {
            $this->roleMiddleware->requirePermission('edit_content');
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 403);
        }

        $sectionKey = isset($_GET['section_key']) && is_string($_GET['section_key'])
            ? trim($_GET['section_key'])
            : null;
        if ($sectionKey === '') {
            $sectionKey = null;
        }

        try {
            $list = $this->mediaService->listAll($sectionKey);
            $this->successResponse($list);
        } catch (\Exception $e) {
            $this->errorResponse('Error al listar media', 500);
        }
    }

    /**
     * DELETE /api/media/{id}
     *
     * 403: sin permiso. 404: media no encontrado.
     */
    public function delete(int $id): void
    {
        try {
            $this->roleMiddleware->requirePermission('edit_content');
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 403);
        }

        try {
            $this->mediaService->delete($id);
            $userId = isset($_SERVER['AUTH_USER_ID']) ? (int) $_SERVER['AUTH_USER_ID'] : null;
            ActivityLogService::log($userId, 'DELETE', 'media', (string) $id, 'Media eliminado');
            $this->successResponse(null, 'Media eliminado correctamente');
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'no encontrado') !== false) {
                $this->errorResponse($e->getMessage(), 404);
            }
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse('Error al eliminar', 500);
        }
    }

    /**
     * GET /api/public/media
     *
     * Sin autenticación. Query opcional: section_key.
     * Devuelve lista con URLs públicas para usar en la landing.
     */
    public function publicIndex(): void
    {
        $sectionKey = isset($_GET['section_key']) && is_string($_GET['section_key'])
            ? trim($_GET['section_key'])
            : null;
        if ($sectionKey === '') {
            $sectionKey = null;
        }

        try {
            $list = $this->mediaService->listPublic($sectionKey);
            $this->successResponse($list);
        } catch (\Exception $e) {
            $this->errorResponse('Error al listar media', 500);
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
