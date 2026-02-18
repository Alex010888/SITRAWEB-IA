<?php
/**
 * Script de prueba para el Defensor Laboral IA.
 * Ejecutar: php test_defensor.php
 * O visitar: http://localhost/sitra_web/test_defensor.php
 */
declare(strict_types=1);

define('BASE_PATH', __DIR__);
require BASE_PATH . '/app/core/Autoloader.php';
\App\Core\Autoloader::register(BASE_PATH . '/app');

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Test Defensor Laboral IA</h1>\n";

try {
    $service = new \App\Services\DefensorService();
    $service->loadChunks();
    echo "<p>✓ meta.jsonl cargado correctamente</p>\n";

    $preguntas = ['Bonificacion', 'vacaciones', 'aguinaldo'];
    foreach ($preguntas as $p) {
        echo "<h2>Consulta: \"{$p}\"</h2>\n";
        $result = $service->consulta($p);
        if ($result['success']) {
            echo "<p><strong>Respuesta:</strong></p>\n<pre>" . htmlspecialchars($result['respuesta']) . "</pre>\n";
            echo "<p><strong>Fuentes:</strong> " . count($result['fuentes']) . "</p>\n";
        } else {
            echo "<p style='color:red'>Error: " . htmlspecialchars($result['message'] ?? '') . "</p>\n";
        }
    }
} catch (\Throwable $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
