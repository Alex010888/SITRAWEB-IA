<?php
/**
 * Database Configuration (PDO MySQL / PostgreSQL)
 * Soporta DATABASE_URL (Render Postgres) o DB_* (MySQL local).
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

        $url = \env('DATABASE_URL', '');
        if ($url !== '' && $url !== null) {
            self::$instance = self::fromUrl($url);
            return self::$instance;
        }

        $host = \env('DB_HOST', '127.0.0.1');
        $port = \env('DB_PORT', '3306');
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
            if (\env('API_DEBUG', false)) {
                throw new \RuntimeException("Database connection error: " . $e->getMessage());
            }
            throw new \RuntimeException("Database connection error");
        }
    }

    /**
     * Conexión desde DATABASE_URL (ej. Render Postgres: postgresql://user:pass@host:5432/dbname)
     */
    private static function fromUrl(string $url): PDO
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            throw new \RuntimeException("DATABASE_URL inválida");
        }

        $scheme = $parsed['scheme'];
        $user = $parsed['user'] ?? '';
        $pass = $parsed['pass'] ?? '';
        $host = $parsed['host'];
        $port = $parsed['port'] ?? ($scheme === 'postgres' || $scheme === 'postgresql' ? 5432 : 3306);
        $path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';

        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$path}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$path};charset=utf8mb4";
        }

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
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
