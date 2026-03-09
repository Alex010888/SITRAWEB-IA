<?php
/**
 * Script para insertar un usuario en la tabla usuarios.
 * Uso: php insert_user.php
 * (Ejecutar desde la carpeta backend, con .env configurado)
 */
declare(strict_types=1);

require __DIR__ . '/app/config/env.php';
loadEnv(__DIR__ . '/.env');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

use App\Models\User;
use App\Config\Database;

$email = 'aeeu50634@mail.com';
$password = 'alex.8891';
$nombre = 'Usuario Prueba';

try {
    $userModel = new User();
    $existente = $userModel->findByEmail($email);
    if ($existente) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE usuarios SET password = :p, nombre = :n, activo = 1 WHERE id = :id');
        $stmt->execute(['p' => $hash, 'n' => $nombre, 'id' => $existente['id']]);
        echo "Usuario actualizado: $email\n";
    } else {
        $id = $userModel->create([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => 'editor',
            'activo' => 1,
        ]);
        echo "Usuario creado correctamente.\n";
        echo "  ID: $id\n";
        echo "  Email: $email\n";
        echo "  Contraseña: $password\n";
    }
    echo "\nPuedes iniciar sesión en http://localhost:3000/login con:\n  Email: $email\n  Contraseña: $password\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
