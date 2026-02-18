<?php
/**
 * User Model
 * 
 * Gestión de usuarios con validación y sanitización
 */

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca un usuario por email
     * 
     * @param string $email
     * @return array|null Usuario o null si no existe
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, email, password, rol, activo, created_at 
             FROM usuarios 
             WHERE email = :email 
             LIMIT 1"
        );

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Busca un usuario por ID
     * 
     * @param int $id
     * @return array|null Usuario (sin password) o null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, email, rol, activo, created_at 
             FROM usuarios 
             WHERE id = :id 
             LIMIT 1"
        );

        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Lista todos los usuarios (sin passwords)
     * 
     * @return array Lista de usuarios
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, email, rol, activo, created_at 
             FROM usuarios 
             ORDER BY created_at DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo usuario
     * 
     * @param array $data ['nombre', 'email', 'password', 'rol'?, 'activo'?]
     * @return int ID del usuario creado
     * @throws \RuntimeException Si falla la validación o inserción
     */
    public function create(array $data): int
    {
        // Validación
        $this->validate($data);

        // Verificar email único
        if ($this->findByEmail($data['email'])) {
            throw new \RuntimeException("El email ya está registrado");
        }

        // Hash del password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol, activo) 
             VALUES (:nombre, :email, :password, :rol, :activo)"
        );

        $stmt->execute([
            'nombre' => trim($data['nombre']),
            'email' => strtolower(trim($data['email'])),
            'password' => $passwordHash,
            'rol' => $data['rol'] ?? 'editor',
            'activo' => $data['activo'] ?? 1,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un usuario
     * 
     * @param int $id
     * @param array $data Campos a actualizar
     * @return bool True si se actualizó
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['nombre', 'email', 'rol', 'activo'];
        $fields = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $field === 'email' 
                    ? strtolower(trim($data[$field])) 
                    : $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Cambia el password de un usuario
     * 
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            throw new \RuntimeException("El password debe tener al menos 6 caracteres");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        return $stmt->execute(['password' => $passwordHash, 'id' => $id]);
    }

    /**
     * Elimina (soft delete) un usuario
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Valida datos de usuario
     * 
     * @param array $data
     * @throws \RuntimeException Si la validación falla
     */
    private function validate(array $data): void
    {
        if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
            throw new \RuntimeException("El nombre es requerido (mínimo 2 caracteres)");
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("Email inválido");
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new \RuntimeException("El password debe tener al menos 6 caracteres");
        }

        $rolesValidos = ['superadmin', 'directivo', 'editor'];
        if (isset($data['rol']) && !in_array($data['rol'], $rolesValidos, true)) {
            throw new \RuntimeException("Rol inválido");
        }
    }

    /**
     * Verifica si un usuario tiene un rol específico
     * 
     * @param int $userId
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(int $userId, string|array $roles): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user['rol'], $roles, true);
    }
}
