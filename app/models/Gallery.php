<?php

namespace App\Models;

use App\Core\Model;

final class Gallery extends Model
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function latest(int $limit = 12): array
    {
        $limit = max(1, min(100, (int)$limit));
        $sql = "SELECT id, title, image_path, created_at
                FROM galeria
                ORDER BY created_at DESC, id DESC
                LIMIT {$limit}";

        return $this->db()->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        $sql = "SELECT id, title, image_path, created_at
                FROM galeria
                ORDER BY created_at DESC, id DESC";

        return $this->db()->query($sql)->fetchAll();
    }

    public function create(string $title, string $imagePath): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO galeria (title, image_path, created_at, updated_at)
             VALUES (:title, :image_path, NOW(), NOW())"
        );
        $stmt->execute([
            ':title' => $title,
            ':image_path' => $imagePath,
        ]);

        return (int)$this->db()->lastInsertId();
    }
}

