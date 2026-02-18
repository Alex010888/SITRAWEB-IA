<?php
/**
 * Activity Log Service
 *
 * Punto central para crear entradas de auditoría.
 * Falla en silencio para no interrumpir el flujo principal.
 * No almacena datos sensibles (passwords, tokens).
 */

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    /**
     * Registra una acción en el audit trail.
     * Captura IP y User-Agent automáticamente si no se pasan.
     *
     * @param int|null $userId ID del usuario (null si no identificado, ej. logout sin token)
     * @param string $action LOGIN, LOGOUT, UPDATE, UPLOAD, DELETE, CREATE, etc.
     * @param string $entity section, media, user, auth
     * @param string|null $entityId ID o key del recurso afectado
     * @param string|null $description Descripción legible
     * @return void No lanza excepciones (fail silently)
     */
    public static function log(
        ?int $userId,
        string $action,
        string $entity,
        ?string $entityId = null,
        ?string $description = null
    ): void {
        try {
            $model = new ActivityLog();
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            if (is_string($ip) && strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $model->create([
                'user_id' => $userId,
                'action' => substr($action, 0, 50),
                'entity' => substr($entity, 0, 50),
                'entity_id' => $entityId !== null ? substr((string) $entityId, 0, 100) : null,
                'description' => $description !== null ? substr($description, 0, 65535) : null,
                'ip_address' => $ip !== null ? substr($ip, 0, 45) : null,
                'user_agent' => $userAgent !== null ? substr($userAgent, 0, 65535) : null,
            ]);
        } catch (\Throwable $e) {
            // Fail silently: no romper el flujo principal por un fallo de logging
        }
    }
}
