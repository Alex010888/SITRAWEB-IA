## Ejecutar en XAMPP (Windows)

1) Inicia **Apache** y **MySQL** en XAMPP.

2) Crea la base de datos e importa el script:
- Abre `http://localhost/phpmyadmin`
- Importa el archivo `db.sql` (este proyecto) o pega su contenido en SQL.

3) Configura credenciales:
- Edita `config/database.php` y ajusta `username/password` si aplica.

4) Activa rutas limpias (si no están):
- Asegúrate que `mod_rewrite` esté habilitado en Apache.
- En `httpd.conf` o `httpd-vhosts.conf`, permite `AllowOverride All` para esta carpeta.

5) Abre la landing:
- URL recomendada (con rewrite del root): `http://localhost/sitra_web/`
- Alternativa directa: `http://localhost/sitra_web/public/`

Notas:
- Los PDFs de `public/docs/*.pdf` son placeholders; reemplázalos por los reales.
- La subida pública de galería está deshabilitada por seguridad. Si quieres probarla, cambia `public_gallery_upload` a `true` en `config/app.php`.

