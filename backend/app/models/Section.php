<?php
/**
 * Section Model
 * 
 * Gestiona las secciones dinámicas del CMS.
 * Cada sección tiene un key único y contenido JSON.
 */

namespace App\Models;

use App\Config\Database;
use PDO;

class Section
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene todas las secciones
     * 
     * @return array Lista de secciones con content decodificado
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT id, section_key, content, updated_at 
             FROM sections 
             ORDER BY section_key ASC"
        );

        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar JSON en cada sección
        return array_map(function($section) {
            $section['content'] = json_decode($section['content'], true);
            return $section;
        }, $sections);
    }

    /**
     * Obtiene una sección por su key
     * 
     * @param string $key
     * @return array|null Sección con content decodificado, o null si no existe
     */
    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, section_key, content, updated_at 
             FROM sections 
             WHERE section_key = ?"
        );
        $stmt->execute([$key]);

        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            return null;
        }

        // Decodificar JSON
        $section['content'] = json_decode($section['content'], true);

        return $section;
    }

    /**
     * Actualiza el contenido de una sección
     * 
     * Si la sección no existe, la crea automáticamente.
     * 
     * @param string $key
     * @param array $content Contenido a almacenar (será codificado a JSON)
     * @return bool True si se actualizó/creó correctamente
     * @throws \RuntimeException Si falla la codificación JSON
     */
    public function upsert(string $key, array $content): bool
    {
        // Validar que el contenido sea válido
        $jsonContent = json_encode($content, JSON_UNESCAPED_UNICODE);
        
        if ($jsonContent === false) {
            throw new \RuntimeException("Error al codificar contenido a JSON");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO sections (section_key, content, updated_at) 
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE 
                content = VALUES(content),
                updated_at = NOW()"
        );

        return $stmt->execute([$key, $jsonContent]);
    }

    /**
     * Elimina una sección por su key
     * 
     * @param string $key
     * @return bool True si se eliminó
     */
    public function delete(string $key): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sections WHERE section_key = ?");
        return $stmt->execute([$key]);
    }

    /**
     * Verifica si una sección existe
     * 
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sections WHERE section_key = ?");
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene la fecha de última actualización de una sección
     * 
     * @param string $key
     * @return string|null Timestamp en formato MySQL
     */
    public function getLastUpdated(string $key): ?string
    {
        $stmt = $this->db->prepare("SELECT updated_at FROM sections WHERE section_key = ?");
        $stmt->execute([$key]);
        
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }
}
