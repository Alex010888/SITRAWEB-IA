-- =========================================================
-- SITRA WEB - API Backend - Users & Authentication
-- =========================================================

USE sitra_web;

-- -------------------------
-- Tabla: usuarios (para API/Admin)
-- -------------------------
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
  rol ENUM('superadmin', 'directivo', 'editor') NOT NULL DEFAULT 'editor',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuarios_email (email),
  INDEX idx_usuarios_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Usuario de prueba (superadmin)
-- -------------------------
-- Password: admin123
INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES
('Administrador', 'admin@sitracabana.org', '$2y$10$CxzbchetoMoOKOUSUx933.McKi/Z2uoNq.qmiZPjEdJkjsUa5.3li', 'superadmin', 1);

-- Nota: El hash corresponde a "admin123"
-- Para generar otro: C:\xampp\php\php.exe -r "echo password_hash('tu_password', PASSWORD_BCRYPT);"
