<?php

namespace App\Core;

use PDO;

/**
 * Modelo base.
 */
abstract class Model
{
    protected function db(): PDO
    {
        return Database::pdo();
    }
}

