<?php

namespace App\Core;

/**
 * Utilidad para subir archivos (imágenes).
 */
final class Upload
{
    /**
     * @return array{ok:bool, filename?:string, error?:string}
     */
    public static function image(array $file, string $targetDirAbs, string $targetUrlPrefix = '/img/gallery'): array
    {
        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Error al subir el archivo.'];
        }

        $maxBytes = 3 * 1024 * 1024; // 3MB
        if (isset($file['size']) && (int)$file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'La imagen excede 3MB.'];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Archivo inválido.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => 'Formato no permitido (solo JPG/PNG/WEBP/GIF/SVG).'];
        }

        if (!is_dir($targetDirAbs)) {
            @mkdir($targetDirAbs, 0755, true);
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = rtrim($targetDirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'No se pudo guardar la imagen.'];
        }

        $publicPath = rtrim($targetUrlPrefix, '/') . '/' . $name;
        return ['ok' => true, 'filename' => $publicPath];
    }
}

