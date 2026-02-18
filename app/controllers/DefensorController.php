<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DefensorService;

/**
 * Defensor Laboral IA - API para consultas RAG
 * Código de Trabajo y Contrato Colectivo SITRACABAÑA.
 */
final class DefensorController extends Controller
{
    public function consulta(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido', 'respuesta' => ''], 405);
            return;
        }

        $input = file_get_contents('php://input');
        $data = is_string($input) ? json_decode($input, true) : null;
        $data = is_array($data) ? $data : [];

        $pregunta = trim((string)($data['pregunta'] ?? $data['query'] ?? ''));
        $historial = is_array($data['historial'] ?? null) ? $data['historial'] : [];

        try {
            $service = new DefensorService();
            $result = $service->consulta($pregunta, $historial);

            if (!$result['success']) {
                $this->json($result, 400);
                return;
            }

            $this->json($result);
        } catch (\Throwable $e) {
            error_log('[DefensorController] Error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'respuesta' => 'Ocurrió un error al procesar tu consulta. Intenta de nuevo.',
                'fuentes' => [],
            ], 500);
        }
    }

    public function health(): void
    {
        header('Access-Control-Allow-Origin: *');

        try {
            $service = new DefensorService();
            $service->loadChunks();
            $this->json(['status' => 'ok', 'message' => 'Defensor Laboral IA listo']);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }
    }
}
