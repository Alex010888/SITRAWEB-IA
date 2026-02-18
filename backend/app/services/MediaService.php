<?php
/**
 * Media Service
 *
 * Lógica de negocio para gestión de media: validación MIME/tamaño,
 * nombres seguros, guardado en images/documents, eliminación en disco.
 */

namespace App\Services;

use App\Models\Media;

class MediaService
{
    private Media $mediaModel;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/svg+xml',
    ];

    private const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
    ];

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
    ];

    /** Ruta base de uploads (backend/uploads) */
    private string $uploadsBasePath;

    public function __construct()
    {
        $this->mediaModel = new Media();
        $this->uploadsBasePath = realpath(__DIR__ . '/../../uploads') ?: (__DIR__ . '/../../uploads');
    }

    /**
     * Tamaño máximo de archivo en bytes (configurable vía .env)
     */
    public function getMaxFileSize(): int
    {
        $mb = (int) env('MEDIA_MAX_FILE_SIZE_MB', 5);
        $mb = max(1, min(50, $mb));
        return $mb * 1024 * 1024;
    }

    /**
     * Sube un archivo: valida, guarda en disco y registra en BD
     *
     * @param array $file Elemento de $_FILES (ej. $_FILES['file'])
     * @param string|null $sectionKey Sección CMS asociada
     * @return array Registro creado con id, path, etc.
     * @throws \RuntimeException Si validación falla o error de escritura
     */
    public function upload(array $file, ?string $sectionKey = null): array
    {
        $this->validateUpload($file);

        $mime = $this->getDetectedMimeType($file['tmp_name'], $file['type']);
        $this->validateMimeType($mime);

        $maxSize = $this->getMaxFileSize();
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException(
                'El archivo excede el tamaño máximo permitido (' . ($maxSize / 1024 / 1024) . ' MB)'
            );
        }

        $isImage = in_array($mime, self::ALLOWED_IMAGE_TYPES, true);
        $subdir = $isImage ? 'images' : 'documents';
        $extension = self::MIME_EXTENSIONS[$mime] ?? 'bin';
        $safeFilename = $this->generateSafeFilename($extension);
        $relativePath = $subdir . '/' . $safeFilename;
        $absolutePath = $this->resolveUploadPath($relativePath);

        $this->ensureUploadDirExists(dirname($absolutePath));

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('No se pudo guardar el archivo en el servidor');
        }

        $originalName = $this->sanitizeOriginalName($file['name'] ?? 'file');
        $id = $this->mediaModel->create([
            'filename' => $safeFilename,
            'original_name' => $originalName,
            'path' => $relativePath,
            'mime_type' => $mime,
            'size' => (int) $file['size'],
            'section_key' => $this->normalizeSectionKey($sectionKey),
        ]);

        $record = $this->mediaModel->findById($id);
        if (!$record) {
            @unlink($absolutePath);
            throw new \RuntimeException('Error al registrar el archivo');
        }
        return $record;
    }

    /**
     * Lista media (admin). Opcional filtro por section_key
     *
     * @param string|null $sectionKey
     * @return array
     */
    public function listAll(?string $sectionKey = null): array
    {
        return $this->mediaModel->getAll($sectionKey);
    }

    /**
     * Lista media para uso público: solo datos necesarios y URLs públicas
     *
     * @param string|null $sectionKey Filtrar por sección (opcional)
     * @return array Cada ítem con url (o path), original_name, mime_type, size, section_key
     */
    public function listPublic(?string $sectionKey = null): array
    {
        $rows = $this->mediaModel->getAll($sectionKey);
        $uploadsBaseUrl = rtrim(env('UPLOADS_BASE_URL', $this->guessUploadsBaseUrl()), '/');

        return array_map(function (array $row) use ($uploadsBaseUrl) {
            $url = $uploadsBaseUrl . '/uploads/' . $row['path'];
            return [
                'id' => $row['id'],
                'url' => $url,
                'path' => $row['path'],
                'original_name' => $row['original_name'],
                'mime_type' => $row['mime_type'],
                'size' => (int) $row['size'],
                'section_key' => $row['section_key'],
                'created_at' => $row['created_at'],
            ];
        }, $rows);
    }

    /**
     * Elimina un media por ID (registro y archivo en disco)
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException Si no existe
     */
    public function delete(int $id): bool
    {
        $record = $this->mediaModel->findById($id);
        if (!$record) {
            throw new \RuntimeException('Media no encontrado');
        }

        $absolutePath = $this->resolveUploadPath($record['path']);
        if (file_exists($absolutePath) && is_file($absolutePath)) {
            if (!@unlink($absolutePath)) {
                // Log en producción; continuamos con borrado en BD
            }
        }

        return $this->mediaModel->delete($id);
    }

    /**
     * Obtiene un registro por ID (admin)
     */
    public function getById(int $id): ?array
    {
        return $this->mediaModel->findById($id);
    }

    private function validateUpload(array $file): void
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('No se recibió ningún archivo o el archivo no es válido');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el límite del servidor',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño permitido',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió solo parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Error de configuración del servidor',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
                UPLOAD_ERR_EXTENSION => 'Una extensión del servidor detuvo la subida',
            ];
            $msg = $messages[$file['error']] ?? 'Error desconocido en la subida';
            throw new \RuntimeException($msg);
        }
    }

    private function getDetectedMimeType(string $tmpPath, string $clientType): string
    {
        if (function_exists('mime_content_type') && file_exists($tmpPath)) {
            $detected = @mime_content_type($tmpPath);
            if ($detected && $this->isAllowedMime($detected)) {
                return $detected;
            }
        }
        $allowed = array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_DOCUMENT_TYPES);
        if (in_array($clientType, $allowed, true)) {
            return $clientType;
        }
        throw new \RuntimeException('Tipo de archivo no permitido. Use imagen (JPEG, PNG, WebP, SVG) o PDF.');
    }

    private function isAllowedMime(string $mime): bool
    {
        return in_array($mime, self::ALLOWED_IMAGE_TYPES, true)
            || in_array($mime, self::ALLOWED_DOCUMENT_TYPES, true);
    }

    private function validateMimeType(string $mime): void
    {
        if (!$this->isAllowedMime($mime)) {
            throw new \RuntimeException('Tipo de archivo no permitido. Use imagen (JPEG, PNG, WebP, SVG) o PDF.');
        }
    }

    /** Genera nombre único seguro (sin path, sin caracteres especiales) */
    private function generateSafeFilename(string $extension): string
    {
        $safe = bin2hex(random_bytes(8)) . '_' . time();
        return $safe . '.' . preg_replace('/[^a-z0-9]/', '', strtolower($extension));
    }

    /** Resuelve ruta absoluta evitando directory traversal */
    private function resolveUploadPath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $parts = array_filter(explode('/', $relativePath), fn($p) => $p !== '' && $p !== '.');
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
                continue;
            }
            $resolved[] = $part;
        }
        $path = $this->uploadsBasePath . '/' . implode('/', $resolved);
        $realBase = realpath($this->uploadsBasePath) ?: $this->uploadsBasePath;
        $realPath = realpath($path);
        if ($realPath !== false && strpos($realPath, $realBase) === 0) {
            return $realPath;
        }
        return $this->uploadsBasePath . '/' . implode('/', $resolved);
    }

    private function ensureUploadDirExists(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new \RuntimeException('No se pudo crear el directorio de subidas');
            }
        }
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w\s\-\.]/u', '_', $name);
        return substr($name, 0, 255) ?: 'file';
    }

    private function normalizeSectionKey(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }
        $key = trim($key);
        if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $key)) {
            return null;
        }
        return $key;
    }

    /** Base URL para servir uploads (origen + path hasta backend, sin /uploads) */
    private function guessUploadsBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // Ej: /sitra_web/backend/public/index.php -> /sitra_web/backend
        $base = dirname(dirname($script));
        if ($base === '/' || $base === '\\') {
            $base = '';
        }
        return ($https ? 'https://' : 'http://') . $host . $base;
    }
}
