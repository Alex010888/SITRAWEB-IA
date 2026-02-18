<?php

namespace App\Core;

/**
 * Controller base.
 */
abstract class Controller
{
    /**
     * Renderiza una vista dentro del layout principal.
     *
     * @param array<string,mixed> $data
     */
    protected function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);

        $viewFile = dirname(__DIR__) . '/views/' . ltrim($view, '/') . '.php';
        if (!is_file($viewFile)) {
            echo 'Vista no encontrada.';
            return;
        }

        $layoutFile = dirname(__DIR__) . '/views/layouts/main.php';
        $data['__viewFile'] = $viewFile;

        extract($data, EXTR_SKIP);
        require $layoutFile;
    }

    protected function redirect(string $to): void
    {
        header('Location: ' . url($to));
        exit;
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

