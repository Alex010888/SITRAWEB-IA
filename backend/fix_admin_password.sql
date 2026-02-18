-- =========================================
-- ACTUALIZACIÓN RÁPIDA: Arreglar password del admin
-- =========================================

USE sitra_web;

-- Eliminar usuario anterior si existe
DELETE FROM usuarios WHERE email = 'admin@sitracabana.org';

-- Insertar con hash correcto
INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES
('Administrador', 'admin@sitracabana.org', '$2y$10$CxzbchetoMoOKOUSUx933.McKi/Z2uoNq.qmiZPjEdJkjsUa5.3li', 'superadmin', 1);

-- Verificar
SELECT id, nombre, email, rol, activo FROM usuarios WHERE email = 'admin@sitracabana.org';
