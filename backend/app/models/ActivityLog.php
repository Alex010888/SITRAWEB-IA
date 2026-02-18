<?php
/**
 * Activity Log Model
 *
 * Acceso de solo lectura a la tabla activity_logs (audit trail).
 */

namespace App\Models;

use App\Config\Database;
use PDO;

class ActivityLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Inserta un registro de auditoría (solo uso interno vía ActivityLogService)
     *
     * @param array $data [user_id?, action, entity, entity_id?, description?, ip_address?, user_agent?]
     * @return bool
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs (user_id, action, entity, entity_id, description, ip_address, user_agent)
             VALUES (:user_id, :action, :entity, :entity_id, :description, :ip_address, :user_agent)"
        );
        return $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'],
            'entity' => $data['entity'],
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Lista logs con paginación y filtros opcionales
     *
     * @param int $page Página (1-based)
     * @param int $perPage Registros por página
     * @param string|null $entity Filtrar por entity (section, media, user, auth)
     * @param int|null $userId Filtrar por user_id
     * @return array ['items' => [...], 'total' => int, 'page' => int, 'per_page' => int]
     */
    public function getPaginated(int $page = 1, int $perPage = 20, ?string $entity = null, ?int $userId = null): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if ($entity !== null && $entity !== '') {
            $where[] = 'entity = :entity';
            $params['entity'] = $entity;
        }
        if ($userId !== null) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM activity_logs $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        $sql = "SELECT id, user_id, action, entity, entity_id, description, ip_address, created_at
                FROM activity_logs
                $whereClause
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === 'limit' || $k === 'offset') {
                $stmt->bindValue(':' . $k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $k, $v);
            }
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
