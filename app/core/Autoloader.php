<?php

namespace App\Core;

/**
 * Autoloader simple PSR-4-like (sin Composer).
 */
final class Autoloader
{
    public static function register(string $baseDir): void
    {
        spl_autoload_register(function (string $class) use ($baseDir) {
            $prefix = 'App\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            $file = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

            if (!is_file($file)) {
                // Fallback para estructura en minúsculas (controllers/models/views/core)
                $file = str_replace(
                    [
                        DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR,
                    ],
                    [
                        DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR,
                    ],
                    $file
                );
            }

            if (is_file($file)) {
                require $file;
            }
        });
    }
}

