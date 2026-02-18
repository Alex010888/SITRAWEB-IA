<?php

namespace App\Models;

use App\Core\Model;

final class Board extends Model
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        $sql = "SELECT id, name, position, photo_path, sort_order
                FROM directiva
                ORDER BY sort_order ASC, id ASC";

        return $this->db()->query($sql)->fetchAll();
    }
}

