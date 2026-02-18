<?php

namespace App\Models;

use App\Core\Model;

final class News extends Model
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function latest(int $limit = 6): array
    {
        $limit = max(1, min(50, (int)$limit));
        $sql = "SELECT id, title, slug, excerpt, image_path, published_at
                FROM noticias
                WHERE is_published = 1
                ORDER BY published_at DESC, id DESC
                LIMIT {$limit}";

        return $this->db()->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function allPublished(): array
    {
        $sql = "SELECT id, title, slug, excerpt, content, image_path, published_at
                FROM noticias
                WHERE is_published = 1
                ORDER BY published_at DESC, id DESC";

        return $this->db()->query($sql)->fetchAll();
    }
}

