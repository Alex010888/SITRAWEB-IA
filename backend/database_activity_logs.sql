-- ============================================
-- MÓDULO 6: ACTIVITY LOGS / AUDIT TRAIL
-- ============================================
-- Tabla de solo lectura para auditoría de acciones del panel admin.

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL COMMENT 'Usuario que realizó la acción (nullable si no identificado)',
    action VARCHAR(50) NOT NULL COMMENT 'LOGIN, LOGOUT, UPDATE, UPLOAD, DELETE, CREATE, etc.',
    entity VARCHAR(50) NOT NULL COMMENT 'section, media, user, auth',
    entity_id VARCHAR(100) DEFAULT NULL COMMENT 'ID o key del recurso afectado',
    description TEXT COMMENT 'Descripción legible de la acción',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 o IPv6',
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_entity_created (entity, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
