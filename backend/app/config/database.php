<?php
/**
 * Database Configuration (PDO MySQL)
 * Conexión Singleton con manejo de errores
 */

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtiene la instancia PDO (Singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = \env('DB_HOST', '127.0.0.1');
        $port = \env('DB_PORT', 3306);
        $dbname = \env('DB_NAME', 'sitra_web');
        $user = \env('DB_USER', 'root');
        $pass = \env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return self::$instance;
        } catch (PDOException $e) {
            // En producción, no revelar detalles de la excepción
            if (\env('API_DEBUG', false)) {
                throw new \RuntimeException("Database connection error: " . $e->getMessage());
            }
            throw new \RuntimeException("Database connection error");
        }
    }

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Prevenir unserialize
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
