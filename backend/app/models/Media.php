<?php
/**
 * Media Model
 *
 * Acceso a datos de la tabla media (archivos subidos: imágenes y PDFs).
 */

namespace App\Models;

use App\Config\Database;
use PDO;

class Media
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Crea un registro de media
     *
     * @param array $data [filename, original_name, path, mime_type, size, section_key?]
     * @return int ID del registro creado
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO media (filename, original_name, path, mime_type, size, section_key)
             VALUES (:filename, :original_name, :path, :mime_type, :size, :section_key)"
        );
        $stmt->execute([
            'filename' => $data['filename'],
            'original_name' => $data['original_name'],
            'path' => $data['path'],
            'mime_type' => $data['mime_type'],
            'size' => (int) $data['size'],
            'section_key' => $data['section_key'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Lista todos los registros de media (admin)
     *
     * @param string|null $sectionKey Filtrar por section_key (opcional)
     * @return array
     */
    public function getAll(?string $sectionKey = null): array
    {
        if ($sectionKey !== null) {
            $stmt = $this->db->prepare(
                "SELECT id, filename, original_name, path, mime_type, size, section_key, created_at
                 FROM media WHERE section_key = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$sectionKey]);
        } else {
            $stmt = $this->db->query(
                "SELECT id, filename, original_name, path, mime_type, size, section_key, created_at
                 FROM media ORDER BY created_at DESC"
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro por ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, filename, original_name, path, mime_type, size, section_key, created_at
             FROM media WHERE id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Elimina un registro por ID
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM media WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
