-- ============================================
-- MÓDULO 4: MEDIA MANAGEMENT
-- ============================================
-- Tabla para archivos subidos (imágenes y PDFs) asociados a secciones CMS

CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL COMMENT 'Nombre único en disco',
    original_name VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    path VARCHAR(512) NOT NULL COMMENT 'Ruta relativa: images/xxx o documents/xxx',
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL COMMENT 'Tamaño en bytes',
    section_key VARCHAR(50) DEFAULT NULL COMMENT 'Sección CMS asociada (opcional)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_section_key (section_key),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
