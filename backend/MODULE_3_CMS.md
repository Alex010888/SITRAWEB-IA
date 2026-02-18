# ============================================
# MÓDULO 3: CMS / SECTIONS - DOCUMENTACIÓN
# ============================================

## 📦 RESUMEN

Sistema CMS headless para gestionar secciones dinámicas de la landing page.

- ✅ Almacena contenido en formato JSON
- ✅ API protegida para Admin Panel (requiere JWT + edit_content)
- ✅ API pública para Landing Page (sin autenticación)
- ✅ CRUD completo de secciones
- ✅ Validación de JSON y section_key

## 📁 ARCHIVOS GENERADOS

### Nuevos:
1. ✅ `app/models/Section.php` - Modelo para secciones
2. ✅ `app/services/SectionService.php` - Lógica de negocio
3. ✅ `app/controllers/SectionController.php` - Controlador REST
4. ✅ `database_sections.sql` - Schema SQL + datos iniciales

### Modificados:
5. ✅ `routes/api.php` - Nuevas rutas de secciones

## 🗄️ BASE DE DATOS

### Tabla `sections`

```sql
CREATE TABLE sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    content JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Campos:

- **section_key**: Identificador único (ej: `hero`, `about`, `contact`)
- **content**: Contenido JSON flexible
- **updated_at**: Fecha de última actualización

### Secciones iniciales:

- `hero` - Banner principal
- `about` - Misión, visión, historia
- `contact` - Datos de contacto
- `footer` - Footer del sitio

## 🔌 ENDPOINTS

### 🔒 ADMIN PANEL (Protegidos)

Requieren: **JWT válido** + permiso **edit_content**

#### GET /api/sections
Lista todas las secciones

**Request:**
```bash
curl -X GET "http://localhost/sitra_web/backend/public/api/sections" \
  -H "Authorization: Bearer <TOKEN>"
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "section_key": "hero",
      "content": {
        "title": "SITRACABAÑA",
        "subtitle": "Sindicato de Trabajadores",
        "description": "..."
      },
      "updated_at": "2026-02-06 10:00:00"
    },
    {
      "id": 2,
      "section_key": "about",
      "content": { ... },
      "updated_at": "2026-02-06 09:30:00"
    }
  ]
}
```

#### GET /api/sections/{key}
Obtiene una sección específica

**Request:**
```bash
curl -X GET "http://localhost/sitra_web/backend/public/api/sections/hero" \
  -H "Authorization: Bearer <TOKEN>"
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "section_key": "hero",
    "content": {
      "title": "SITRACABAÑA",
      "subtitle": "Sindicato de Trabajadores",
      "description": "Organización y defensa de derechos laborales"
    },
    "updated_at": "2026-02-06 10:00:00"
  }
}
```

**Response (404):**
```json
{
  "success": false,
  "message": "Sección no encontrada"
}
```

#### PUT /api/sections/{key}
Actualiza o crea una sección

**Request:**
```bash
curl -X PUT "http://localhost/sitra_web/backend/public/api/sections/hero" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "SITRACABAÑA",
      "subtitle": "Sindicato de Trabajadores · Ingenio La Cabaña",
      "description": "Defendiendo tus derechos laborales",
      "cta_primary": "Afíliate ahora",
      "cta_secondary": "Más información"
    }
  }'
```

**Response (200):**
```json
{
  "success": true,
  "message": "Sección actualizada exitosamente",
  "data": {
    "id": 1,
    "section_key": "hero",
    "content": {
      "title": "SITRACABAÑA",
      "subtitle": "Sindicato de Trabajadores · Ingenio La Cabaña",
      "description": "Defendiendo tus derechos laborales",
      "cta_primary": "Afíliate ahora",
      "cta_secondary": "Más información"
    },
    "updated_at": "2026-02-06 10:45:00"
  }
}
```

**Notas:**
- Si la sección no existe, se crea automáticamente
- El `content` debe ser un objeto JSON válido
- Máximo 1MB de contenido por sección

#### DELETE /api/sections/{key}
Elimina una sección (requiere permiso `delete_content`)

**Request:**
```bash
curl -X DELETE "http://localhost/sitra_web/backend/public/api/sections/hero" \
  -H "Authorization: Bearer <TOKEN>"
```

**Response (200):**
```json
{
  "success": true,
  "message": "Sección eliminada exitosamente"
}
```

### 🌐 LANDING PAGE (Públicos)

**Sin autenticación** - Solo lectura

#### GET /api/public/sections
Lista todas las secciones (público)

**Request:**
```bash
curl -X GET "http://localhost/sitra_web/backend/public/api/public/sections"
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "section_key": "hero",
      "content": { ... },
      "updated_at": "2026-02-06 10:00:00"
    },
    {
      "section_key": "about",
      "content": { ... },
      "updated_at": "2026-02-06 09:30:00"
    }
  ]
}
```

**Nota:** No incluye campo `id` por seguridad.

#### GET /api/public/sections/{key}
Obtiene una sección específica (público)

**Request:**
```bash
curl -X GET "http://localhost/sitra_web/backend/public/api/public/sections/hero"
```

**Response (200):**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "section_key": "hero",
    "content": {
      "title": "SITRACABAÑA",
      "subtitle": "Sindicato de Trabajadores",
      "description": "..."
    },
    "updated_at": "2026-02-06 10:00:00"
  }
}
```

## 🎨 EJEMPLOS DE CONTENIDO

### Hero Section

```json
{
  "content": {
    "title": "SITRACABAÑA",
    "subtitle": "Sindicato de Trabajadores · Ingenio La Cabaña",
    "description": "Organización, representación y defensa de los derechos laborales",
    "cta_primary": "Afíliate ahora",
    "cta_secondary": "Conócenos",
    "stats": [
      {
        "icon": "shield-check",
        "title": "Defensa",
        "description": "Acompañamiento laboral"
      },
      {
        "icon": "people",
        "title": "Unidad",
        "description": "Trabajo colectivo"
      }
    ]
  }
}
```

### About Section

```json
{
  "content": {
    "title": "Nosotros",
    "subtitle": "Nuestra razón de ser",
    "mission": {
      "title": "Misión",
      "content": "Representar y proteger a los trabajadores..."
    },
    "vision": {
      "title": "Visión",
      "content": "Ser un sindicato moderno y transparente..."
    },
    "history": {
      "title": "Historia",
      "content": "Nacemos de la necesidad de organizarnos..."
    }
  }
}
```

### Contact Section

```json
{
  "content": {
    "title": "Contacto",
    "email": "contacto@sitracabana.org",
    "phone": "+502 0000-0000",
    "address": "Ingenio La Cabaña, Guatemala",
    "social": {
      "facebook": "https://facebook.com/sitracabana",
      "twitter": "https://twitter.com/sitracabana",
      "youtube": "https://youtube.com/@sitracabana"
    }
  }
}
```

## 🔒 SEGURIDAD

### Validaciones implementadas:

✅ **section_key:**
- Solo alfanuméricos, guiones y guiones bajos
- Entre 2 y 50 caracteres
- No puede estar vacío

✅ **content:**
- Debe ser un objeto JSON válido
- No puede estar vacío
- Máximo 1MB por sección
- Debe ser serializable

✅ **Autenticación:**
- Endpoints admin requieren JWT válido
- Endpoints admin requieren permiso `edit_content`
- DELETE requiere permiso `delete_content`

✅ **SQL Injection:**
- Todas las queries usan PDO prepared statements

## 🧪 FLUJO DE USO

### Desde Admin Panel (React):

1. **Login** → Obtener JWT
2. **Fetch secciones** → `GET /api/sections`
3. **Editar sección** → `PUT /api/sections/{key}` con nuevo content
4. **Ver cambios** → Refrescar landing page

### Desde Landing Page (React):

1. **Fetch secciones** → `GET /api/public/sections`
2. **Renderizar** → Usar `section_key` para identificar componente
3. **Mapear content** → Extraer datos del JSON

## 📊 ARQUITECTURA

```
Admin Panel (React)
    ↓ JWT + edit_content
    ↓
AuthMiddleware → RoleMiddleware → SectionController
    ↓
SectionService (validación)
    ↓
Section Model (PDO)
    ↓
MySQL (tabla sections)


Landing Page (React)
    ↓ Sin autenticación
    ↓
SectionController (endpoints públicos)
    ↓
Section Model
    ↓
MySQL (tabla sections)
```

## 🎯 PERMISOS REQUERIDOS

| Endpoint | Permiso | Roles |
|----------|---------|-------|
| GET /api/sections | edit_content | superadmin, directivo, editor |
| GET /api/sections/{key} | edit_content | superadmin, directivo, editor |
| PUT /api/sections/{key} | edit_content | superadmin, directivo, editor |
| DELETE /api/sections/{key} | delete_content | superadmin |
| GET /api/public/sections | ninguno | público |
| GET /api/public/sections/{key} | ninguno | público |

## ✅ CARACTERÍSTICAS

✅ Headless CMS (contenido separado de presentación)
✅ JSON flexible (cualquier estructura de datos)
✅ Auto-create (PUT crea si no existe)
✅ Validación robusta
✅ Endpoints públicos sin auth
✅ Endpoints admin protegidos
✅ Sin hardcoded keys
✅ Escalable y mantenible

## 🚀 PRÓXIMOS MÓDULOS

El sistema CMS está listo para:
- Módulo 4: Noticias dinámicas
- Módulo 5: Galería de imágenes
- Módulo 6: Documentos descargables
- Módulo 7: Directiva/equipo

## 📝 INSTALACIÓN

1. Ejecutar SQL:
```bash
mysql -u root -p sitra_web < backend/database_sections.sql
```

2. Las rutas ya están registradas en `routes/api.php`

3. Probar endpoints públicos:
```bash
curl http://localhost/sitra_web/backend/public/api/public/sections
```

4. Probar endpoints admin (con token):
```bash
curl -H "Authorization: Bearer <TOKEN>" \
  http://localhost/sitra_web/backend/public/api/sections
```

## 🎉 MÓDULO 3 COMPLETADO

El CMS de secciones está 100% funcional y listo para usar.
