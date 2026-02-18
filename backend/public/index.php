<?php
/**
 * Backend API - Entry Point
 * 
 * Punto de entrada para todas las requests de la API REST.
 * Carga .env, autoload, maneja errores y despacha rutas.
 */

declare(strict_types=1);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0'); // No mostrar errores en producción

// Autoload manual (sin Composer)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Cargar .env
require __DIR__ . '/../app/config/env.php';
loadEnv(__DIR__ . '/../.env');

// Manejo global de errores/excepciones
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $response = [
        'success' => false,
        'message' => 'Error interno del servidor'
    ];

    // En modo debug, mostrar detalles
    if (env('API_DEBUG', false)) {
        $response['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
});

// Headers JSON por defecto
header('Content-Type: application/json; charset=utf-8');

// Despachar rutas
$router = require __DIR__ . '/../routes/api.php';
$router->dispatch();
