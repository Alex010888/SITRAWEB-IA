<?php

namespace App\Models;

use App\Core\Model;

final class Document extends Model
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        $sql = "SELECT id, title, file_path, created_at
                FROM documentos
                ORDER BY created_at DESC, id DESC";

        return $this->db()->query($sql)->fetchAll();
    }
}

