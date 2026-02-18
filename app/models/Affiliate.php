<?php

namespace App\Models;

use App\Core\Model;

final class Affiliate extends Model
{
    public function create(string $name, string $phone, string $email, string $message, string $ip, string $userAgent): int
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO afiliados (name, phone, email, message, ip_address, user_agent, created_at, updated_at)
             VALUES (:name, :phone, :email, :message, :ip, :ua, NOW(), NOW())"
        );

        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':email' => $email,
            ':message' => $message,
            ':ip' => $ip,
            ':ua' => mb_substr($userAgent, 0, 255),
        ]);

        return (int)$this->db()->lastInsertId();
    }
}

