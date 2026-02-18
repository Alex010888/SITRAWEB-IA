<?php
/**
 * Helpers globales (escapar, URLs, CSRF, etc.)
 */

use App\Core\Session;

// Compat PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        return defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return base_url() . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('asset_v')) {
    /**
     * Asset con versionado por filemtime para evitar caché.
     */
    function asset_v(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $abs = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/\\') : '') . '/public' . $path;
        $abs = str_replace('/', DIRECTORY_SEPARATOR, $abs);

        $u = asset($path);
        if ($abs !== '' && is_file($abs)) {
            $v = (string)@filemtime($abs);
            return $u . (str_contains($u, '?') ? '&' : '?') . 'v=' . rawurlencode($v);
        }
        return $u;
    }
}

if (!function_exists('get_cms_section')) {
    /**
     * Lee una sección del CMS (tabla sections, misma BD que el backend API).
     * Retorna el content decodificado (array) o null si no existe o falla.
     */
    function get_cms_section(string $key): ?array
    {
        try {
            $pdo = \App\Core\Database::pdo();
            $stmt = $pdo->prepare('SELECT content FROM sections WHERE section_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row || empty($row['content'])) {
                return null;
            }
            $decoded = json_decode($row['content'], true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('site_logo_path')) {
    /**
     * Retorna el path o URL del logo (editable desde el panel: sección "logo").
     * Si la sección tiene "url", se usa; si es path relativo se devuelve tal cual.
     */
    function site_logo_path(): string
    {
        $section = get_cms_section('logo');
        if ($section !== null && !empty($section['url'])) {
            return (string) $section['url'];
        }
        return '/img/logo.svg';
    }
}

if (!function_exists('site_logo_url')) {
    /**
     * URL final del logo para usar en <img src="">.
     * Si el logo es URL absoluta (http...) se devuelve tal cual; si no, se pasa por asset_v.
     */
    function site_logo_url(): string
    {
        $path = site_logo_path();
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset_v($path);
    }
}

if (!function_exists('uploaded_asset_url')) {
    /**
     * Resuelve la URL de un archivo subido (directiva, logo, etc.).
     * Si ya es URL absoluta (http/https) la devuelve tal cual.
     * Si es path relativo (ej. images/xxx.png), construye la URL al backend uploads.
     */
    function uploaded_asset_url(string $pathOrUrl): string
    {
        if ($pathOrUrl === '') {
            return '';
        }
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }
        $path = ltrim(str_replace('\\', '/', $pathOrUrl), '/');
        $path = preg_replace('#^uploads/#', '', $path);
        $base = defined('BACKEND_UPLOADS_BASE') ? BACKEND_UPLOADS_BASE : '';
        return $base . '/uploads/' . $path;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        Session::start();
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            Session::set('_csrf', $token);
        }
        return $token;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool
    {
        Session::start();
        $sent = $_POST['_csrf'] ?? '';
        $stored = Session::get('_csrf');
        return is_string($sent) && is_string($stored) && hash_equals($stored, $sent);
    }
}

