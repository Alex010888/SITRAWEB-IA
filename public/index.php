<?php
declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

// Paths
define('BASE_PATH', dirname(__DIR__));

// Config
$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'UTC');
define('APP_NAME', (string)($appConfig['name'] ?? ''));
define('PUBLIC_GALLERY_UPLOAD', (bool)($appConfig['public_gallery_upload'] ?? false));
$backendUploadsBase = $appConfig['backend_uploads_base'] ?? null;

// Autoload + helpers
require BASE_PATH . '/app/core/Autoloader.php';
Autoloader::register(BASE_PATH . '/app');
require BASE_PATH . '/app/core/helpers.php';

// Sesión
Session::start();

// BASE_URL autodetect (o override en config/app.php)
$baseUrl = $appConfig['base_url'] ?? null;
if (!is_string($baseUrl) || $baseUrl === '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/'); // ej: /sitra_web/public
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');

    // Si el script está en /public pero la URL visible NO contiene /public, removemos /public del base
    if (str_ends_with($scriptDir, '/public') && strpos($uri, '/public/') === false) {
        $scriptDir = substr($scriptDir, 0, -strlen('/public'));
    }

    $baseUrl = $scriptDir === '/' ? '' : $scriptDir;
}
define('BASE_URL', rtrim($baseUrl, '/'));

if (!is_string($backendUploadsBase) || $backendUploadsBase === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $backendUploadsBase = $scheme . '://' . $host . (BASE_URL !== '' ? BASE_URL : '') . '/backend';
}
define('BACKEND_UPLOADS_BASE', rtrim($backendUploadsBase, '/'));

// Router
$router = new Router();
require BASE_PATH . '/config/routes.php';

$router->dispatch(Request::method(), Request::path());

