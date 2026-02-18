-- =========================================================
-- SITRA WEB - Base de datos (MySQL)
-- =========================================================

CREATE DATABASE IF NOT EXISTS sitra_web
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE sitra_web;

-- -------------------------
-- usuarios (para futuro admin)
-- -------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'admin',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- -------------------------
-- noticias
-- -------------------------
CREATE TABLE IF NOT EXISTS noticias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  excerpt VARCHAR(280) NOT NULL,
  content TEXT NULL,
  image_path VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_noticias_published (is_published, published_at)
) ENGINE=InnoDB;

-- -------------------------
-- galeria
-- -------------------------
CREATE TABLE IF NOT EXISTS galeria (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- -------------------------
-- documentos
-- -------------------------
CREATE TABLE IF NOT EXISTS documentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- -------------------------
-- directiva
-- -------------------------
CREATE TABLE IF NOT EXISTS directiva (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  position VARCHAR(140) NOT NULL,
  photo_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_directiva_sort (sort_order, id)
) ENGINE=InnoDB;

-- -------------------------
-- afiliados (formulario público)
-- -------------------------
CREATE TABLE IF NOT EXISTS afiliados (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(120) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_afiliados_created (created_at)
) ENGINE=InnoDB;

-- -------------------------
-- Datos de ejemplo
-- -------------------------

INSERT INTO usuarios (username, password_hash, role, created_at, updated_at)
VALUES ('admin', '$2y$10$CHANGE_ME_HASH_EN_PANEL', 'admin', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO noticias (title, slug, excerpt, content, image_path, is_published, published_at, created_at, updated_at) VALUES
('Comunicado a la base trabajadora', 'comunicado-base-trabajadora', 'Información importante sobre organización y derechos.', 'Este es un contenido de ejemplo. Sustituye por el comunicado real desde el futuro panel administrativo.', '/img/placeholder-news.svg', 1, NOW(), NOW(), NOW()),
('Reunión informativa semanal', 'reunion-informativa-semanal', 'Agenda, puntos y acuerdos de la reunión informativa.', 'Contenido de ejemplo. Aquí puedes publicar acuerdos, convocatorias y recordatorios.', '/img/placeholder-news.svg', 1, NOW() - INTERVAL 2 DAY, NOW(), NOW()),
('Jornada de capacitación', 'jornada-capacitacion', 'Capacitación y orientación para afiliados.', 'Contenido de ejemplo. Publica fechas, horarios y materiales.', '/img/placeholder-news.svg', 1, NOW() - INTERVAL 5 DAY, NOW(), NOW());

INSERT INTO galeria (title, image_path, created_at, updated_at) VALUES
('Actividad sindical', '/img/gallery/galeria-01.svg', NOW(), NOW()),
('Asamblea', '/img/gallery/galeria-02.svg', NOW(), NOW()),
('Capacitación', '/img/gallery/galeria-03.svg', NOW(), NOW()),
('Jornada', '/img/gallery/galeria-04.svg', NOW(), NOW()),
('Reunión', '/img/gallery/galeria-05.svg', NOW(), NOW()),
('Solidaridad', '/img/gallery/galeria-06.svg', NOW(), NOW());

INSERT INTO documentos (title, file_path, created_at, updated_at) VALUES
('Estatutos (ejemplo)', '/docs/estatutos.pdf', NOW(), NOW()),
('Reglamento interno (ejemplo)', '/docs/reglamento.pdf', NOW(), NOW());

INSERT INTO directiva (name, position, photo_path, sort_order, created_at, updated_at) VALUES
('María López', 'Secretaría General', '/img/directiva/dir-01.svg', 1, NOW(), NOW()),
('Juan Pérez', 'Secretaría de Organización', '/img/directiva/dir-02.svg', 2, NOW(), NOW()),
('Ana García', 'Tesorería', '/img/directiva/dir-03.svg', 3, NOW(), NOW()),
('Carlos Martínez', 'Vocalía', '/img/directiva/dir-04.svg', 4, NOW(), NOW());