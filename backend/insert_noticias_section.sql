INSERT INTO sections (section_key, content) VALUES
('noticias', JSON_OBJECT(
    'title', 'Noticias',
    'subtitle', 'Comunicados y novedades del sindicato. Enlaces a noticias relevantes.',
    'items', JSON_ARRAY(
        JSON_OBJECT('title', '', 'excerpt', '', 'url', '', 'image_url', '/img/placeholder-news.svg', 'source', '', 'date', '')
    )
))
ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = CURRENT_TIMESTAMP;
