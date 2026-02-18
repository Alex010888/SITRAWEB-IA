# Módulo 4: Media Management

Documentación del módulo de gestión de media (imágenes y PDFs) para el panel de administración y la landing pública.

## Instalación

1. **Ejecutar el SQL de la tabla `media`:**
   ```bash
   mysql -u root -p sitra_web < database_media.sql
   ```

2. **Variables de entorno (opcional)** en `.env`:
   ```env
   UPLOADS_BASE_URL=http://localhost/sitra_web/backend
   MEDIA_MAX_FILE_SIZE_MB=5
   ```
   `UPLOADS_BASE_URL` es la URL base para enlaces a archivos (la URL pública será `UPLOADS_BASE_URL/uploads/images/...` o `.../documents/...`).

3. **Permisos:** el usuario del servidor web debe poder escribir en `backend/uploads/images` y `backend/uploads/documents`.

---

## Endpoints

### Protegidos (JWT + permiso `edit_content`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/media/upload` | Subir archivo (multipart) |
| GET | `/api/media` | Listar media (opcional: `?section_key=hero`) |
| DELETE | `/api/media/{id}` | Eliminar media |

### Público (sin autenticación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/public/media` | Listar media con URLs públicas (`?section_key=hero` opcional) |

---

## Ejemplo: petición de subida (multipart)

```http
POST /api/media/upload HTTP/1.1
Host: localhost
Authorization: Bearer <JWT_TOKEN>
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxk

------WebKitFormBoundary7MA4YWxk
Content-Disposition: form-data; name="file"; filename="logo.png"
Content-Type: image/png

<binary data>
------WebKitFormBoundary7MA4YWxk
Content-Disposition: form-data; name="section_key"

hero
------WebKitFormBoundary7MA4YWxk--
```

**cURL:**
```bash
curl -X POST "http://localhost/sitra_web/backend/public/api/media/upload" \
  -H "Authorization: Bearer TU_JWT" \
  -F "file=@/ruta/a/logo.png" \
  -F "section_key=hero"
```

**Campos:**
- `file` (requerido): archivo a subir (imagen JPEG/PNG/WebP o PDF).
- `section_key` (opcional): clave de sección CMS para asociar (ej. `hero`, `about`).

---

## Ejemplo: respuestas JSON

### POST /api/media/upload — 201 Created
```json
{
  "success": true,
  "message": "Archivo subido correctamente",
  "data": {
    "id": 1,
    "filename": "a1b2c3d4e5f6_1234567890.png",
    "original_name": "logo.png",
    "path": "images/a1b2c3d4e5f6_1234567890.png",
    "mime_type": "image/png",
    "size": 15234,
    "section_key": "hero",
    "created_at": "2026-02-08 12:00:00"
  }
}
```

### POST /api/media/upload — 400 Bad Request (archivo inválido)
```json
{
  "success": false,
  "message": "Tipo de archivo no permitido. Use imagen (JPEG, PNG, WebP) o PDF."
}
```

### GET /api/media — 200 OK
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "filename": "a1b2c3d4e5f6_1234567890.png",
      "original_name": "logo.png",
      "path": "images/a1b2c3d4e5f6_1234567890.png",
      "mime_type": "image/png",
      "size": 15234,
      "section_key": "hero",
      "created_at": "2026-02-08 12:00:00"
    }
  ]
}
```

### GET /api/public/media — 200 OK (URLs públicas)
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "url": "http://localhost/sitra_web/backend/uploads/images/a1b2c3d4e5f6_1234567890.png",
      "path": "images/a1b2c3d4e5f6_1234567890.png",
      "original_name": "logo.png",
      "mime_type": "image/png",
      "size": 15234,
      "section_key": "hero",
      "created_at": "2026-02-08 12:00:00"
    }
  ]
}
```

### DELETE /api/media/1 — 200 OK
```json
{
  "success": true,
  "message": "Media eliminado correctamente"
}
```

### 403 Forbidden (sin permiso)
```json
{
  "success": false,
  "message": "No tienes permiso para: edit_content",
  "error": "forbidden"
}
```

---

## URL pública de un archivo

La landing puede usar directamente la URL devuelta en `GET /api/public/media` (campo `url`), por ejemplo:

```
http://localhost/sitra_web/backend/uploads/images/a1b2c3d4e5f6_1234567890.png
```

Para que Apache sirva esos archivos, la carpeta `backend/uploads` debe ser accesible por URL. En XAMPP con la app en `htdocs/sitra_web`, la URL será `http://localhost/sitra_web/backend/uploads/...`. Configura `UPLOADS_BASE_URL` en `.env` para que coincida con tu instalación (ej. `http://localhost/sitra_web/backend`).

---

## Seguridad: .htaccess y servidor

- **`backend/uploads/.htaccess`** (incluido): desactiva la ejecución de PHP en esa carpeta y deniega acceso a `.php`, `.phar`, etc., para que no se ejecuten scripts subidos por error.
- **Recomendaciones adicionales:**
  - En Apache, no usar `AllowOverride None` para `uploads` si quieres que el .htaccess tenga efecto; o aplicar las mismas reglas en el `<Directory>` de `uploads`.
  - Asegurar que el document root o el virtual host no permitan listado de directorios en `uploads` (`Options -Indexes`).
  - Mantener `MEDIA_MAX_FILE_SIZE_MB` dentro de un límite razonable (p. ej. 5–10 MB) y alineado con `upload_max_filesize` y `post_max_size` de PHP.

---

## Tipos MIME permitidos

- **Imágenes:** `image/jpeg`, `image/png`, `image/webp`
- **Documentos:** `application/pdf`

La validación se hace por MIME (incluyendo detección con `mime_content_type` cuando está disponible), no solo por extensión.
