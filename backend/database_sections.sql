-- ============================================
-- MÓDULO 3: CMS / SECTIONS
-- ============================================

-- Tabla para gestionar secciones dinámicas de la landing page
CREATE TABLE IF NOT EXISTS sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identificador único: hero, about, services, etc.',
    content JSON NOT NULL COMMENT 'Contenido dinámico en formato JSON',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section_key (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Secciones iniciales de ejemplo
INSERT INTO sections (section_key, content) VALUES
('hero', JSON_OBJECT(
    'title', 'SITRACABAÑA',
    'subtitle', 'Sindicato de Trabajadores · Ingenio La Cabaña',
    'description', 'Organización, representación y defensa de los derechos laborales con enfoque humano y transparente.',
    'cta_primary', 'Afíliate ahora',
    'cta_secondary', 'Conócenos',
    'stats', JSON_ARRAY(
        JSON_OBJECT('icon', 'shield-check', 'title', 'Defensa', 'description', 'Acompañamiento laboral'),
        JSON_OBJECT('icon', 'people', 'title', 'Unidad', 'description', 'Trabajo colectivo'),
        JSON_OBJECT('icon', 'journal-check', 'title', 'Gestión', 'description', 'Transparencia y orden')
    )
)),
('about', JSON_OBJECT(
    'title', 'Nosotros',
    'subtitle', 'Nuestra razón de ser: servicio, justicia y dignidad.',
    'mission', JSON_OBJECT(
        'title', 'Misión',
        'content', 'Representar y proteger a las y los trabajadores del Ingenio La Cabaña, promoviendo condiciones laborales justas, seguridad, bienestar y respeto a los derechos.'
    ),
    'vision', JSON_OBJECT(
        'title', 'Visión',
        'content', 'Ser un sindicato moderno, transparente y participativo, referente por su gestión y por el impacto positivo en la vida de la base trabajadora.'
    ),
    'history', JSON_OBJECT(
        'title', 'Historia',
        'content', 'Nacemos de la necesidad de organizarnos para dialogar, negociar y construir acuerdos, priorizando la unidad y el respeto. Crecemos con el trabajo diario y el compromiso de cada afiliado.'
    )
)),
('contact', JSON_OBJECT(
    'title', 'Contacto',
    'email', 'contacto@sitracabana.org',
    'phone', '+502 0000-0000',
    'address', 'Ingenio La Cabaña, Guatemala',
    'social', JSON_OBJECT(
        'facebook', 'https://facebook.com/sitracabana',
        'twitter', 'https://twitter.com/sitracabana',
        'youtube', 'https://youtube.com/@sitracabana'
    )
)),
('footer', JSON_OBJECT(
    'copyright', '© 2026 SITRACABAÑA. Todos los derechos reservados.',
    'tagline', 'Hecho con PHP + Bootstrap 5.',
    'links', JSON_ARRAY(
        JSON_OBJECT('label', 'Inicio', 'url', '#'),
        JSON_OBJECT('label', 'Nosotros', 'url', '#nosotros'),
        JSON_OBJECT('label', 'Noticias', 'url', '#noticias'),
        JSON_OBJECT('label', 'Contacto', 'url', '#contacto')
    )
)),
('logo', JSON_OBJECT(
    'url', '/img/logo.png',
    'alt', 'Logo SITRACABAÑA'
)),
('directiva', JSON_OBJECT(
    'title', 'Directiva',
    'subtitle', 'Equipo de trabajo y representación.',
    'members', JSON_ARRAY(
        JSON_OBJECT('name', '', 'position', 'Presidente', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vicepresidente', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Secretario', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Tesorero', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vocal 1', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vocal 2', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vocal 3', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vocal 4', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Vocal 5', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Suplente 1', 'photo_path', '/img/placeholder-person.svg'),
        JSON_OBJECT('name', '', 'position', 'Suplente 2', 'photo_path', '/img/placeholder-person.svg')
    )
)),
('noticias', JSON_OBJECT(
    'title', 'Noticias',
    'subtitle', 'Comunicados y novedades del sindicato. Enlaces a noticias relevantes.',
    'items', JSON_ARRAY(
        JSON_OBJECT('title', 'Ejemplo de noticia externa', 'excerpt', 'Resumen breve de la noticia.', 'url', 'https://ejemplo.com/noticia', 'image_url', '/img/placeholder-news.svg', 'source', 'Fuente', 'date', '')
    )
))
ON DUPLICATE KEY UPDATE 
    content = VALUES(content),
    updated_at = CURRENT_TIMESTAMP;
