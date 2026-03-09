-- Schema mínimo para Render (PostgreSQL)
-- Ejecutar en la base de datos de Render después de crear el Blueprint.
-- En Dashboard: Postgres > Connect > psql o ejecutar este SQL desde la consola.

-- Tabla usuarios (login y panel admin)
CREATE TABLE IF NOT EXISTS usuarios (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'editor' CHECK (rol IN ('superadmin', 'directivo', 'editor')),
  activo SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios (email);
CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios (activo);

-- Usuario admin por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol, activo)
VALUES (
  'Administrador',
  'admin@sitracabana.org',
  '$2y$10$CxzbchetoMoOKOUSUx933.McKi/Z2uoNq.qmiZPjEdJkjsUa5.3li',
  'superadmin',
  1
) ON CONFLICT (email) DO NOTHING;

-- Tablas opcionales para CMS (secciones, media, logs)
CREATE TABLE IF NOT EXISTS sections (
  id SERIAL PRIMARY KEY,
  section_key VARCHAR(50) NOT NULL UNIQUE,
  content JSONB NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS media (
  id SERIAL PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  path VARCHAR(512) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size INTEGER NOT NULL,
  section_key VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
  id SERIAL PRIMARY KEY,
  user_id INTEGER DEFAULT NULL,
  action VARCHAR(50) NOT NULL,
  entity VARCHAR(50) NOT NULL,
  entity_id VARCHAR(100) DEFAULT NULL,
  description TEXT,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
