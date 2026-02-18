# ============================================
# ✅ MÓDULO 3: CMS / SECTIONS - COMPLETADO
# ============================================

## 📋 RESUMEN EJECUTIVO

Sistema CMS headless para gestión dinámica de secciones de landing page.

- ✅ Contenido JSON flexible
- ✅ API pública para React landing
- ✅ API protegida para Admin Panel
- ✅ CRUD completo
- ✅ Integrado con RBAC (Módulo 2)

## 📦 ARCHIVOS ENTREGADOS

### Nuevos archivos core:
1. ✅ `app/models/Section.php` - Modelo de secciones (PDO)
2. ✅ `app/services/SectionService.php` - Lógica de negocio + validación
3. ✅ `app/controllers/SectionController.php` - Endpoints REST (admin + público)
4. ✅ `database_sections.sql` - Schema + datos iniciales

### Archivos modificados:
5. ✅ `routes/api.php` - Rutas de secciones agregadas

### Documentación:
6. ✅ `MODULE_3_CMS.md` - Documentación completa
7. ✅ `EXAMPLES_CMS.sh` - Ejemplos cURL
8. ✅ `postman_collection.json` - Actualizado (v3.0.0)

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

**Secciones iniciales:**
- `hero` - Banner principal
- `about` - Misión/Visión/Historia
- `contact` - Datos de contacto
- `footer` - Footer del sitio

## 🔌 ENDPOINTS

### 🌐 Públicos (Landing Page)

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/public/sections` | No | Lista todas las secciones |
| GET | `/api/public/sections/{key}` | No | Obtiene una sección |

### 🔒 Admin (Panel)

| Método | Endpoint | Auth | Permiso | Descripción |
|--------|----------|------|---------|-------------|
| GET | `/api/sections` | JWT | `edit_content` | Lista todas las secciones |
| GET | `/api/sections/{key}` | JWT | `edit_content` | Obtiene una sección |
| PUT | `/api/sections/{key}` | JWT | `edit_content` | Actualiza/crea sección |
| DELETE | `/api/sections/{key}` | JWT | `delete_content` | Elimina sección |

## 💻 EJEMPLO DE USO

### Landing Page (React):

```javascript
// Fetch todas las secciones
fetch('http://localhost/sitra_web/backend/public/api/public/sections')
  .then(res => res.json())
  .then(data => {
    data.data.forEach(section => {
      // Renderizar según section_key
      if (section.section_key === 'hero') {
        renderHero(section.content);
      } else if (section.section_key === 'about') {
        renderAbout(section.content);
      }
    });
  });
```

### Admin Panel (React):

```javascript
// Actualizar sección hero
fetch('http://localhost/sitra_web/backend/public/api/sections/hero', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    content: {
      title: 'SITRACABAÑA',
      subtitle: 'Nuevo subtítulo',
      description: 'Nueva descripción'
    }
  })
})
  .then(res => res.json())
  .then(data => {
    console.log('Sección actualizada:', data);
  });
```

## 🔒 SEGURIDAD

### Validaciones:

✅ **section_key:**
- Solo alfanuméricos, guiones, guiones bajos
- 2-50 caracteres
- No vacío

✅ **content:**
- Objeto JSON válido
- No vacío
- Máximo 1MB

✅ **Autenticación:**
- Endpoints admin requieren JWT
- Endpoints admin requieren permisos RBAC
- Endpoints públicos sin restricciones

✅ **SQL:**
- PDO prepared statements siempre

## 🎯 PERMISOS

| Acción | Permiso | Roles con acceso |
|--------|---------|------------------|
| Ver secciones (admin) | `edit_content` | superadmin, directivo, editor |
| Editar secciones | `edit_content` | superadmin, directivo, editor |
| Eliminar secciones | `delete_content` | superadmin |
| Ver secciones (público) | ninguno | todos |

## 📊 ARQUITECTURA

```
┌─────────────────────┐
│   Landing Page      │
│     (React)         │
└──────────┬──────────┘
           │ GET /api/public/sections (sin auth)
           ▼
┌─────────────────────┐
│ SectionController   │
│  (publicIndex)      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐     ┌─────────────────────┐
│  Section Model      │────▶│    MySQL            │
│  (PDO queries)      │     │  tabla: sections    │
└─────────────────────┘     └─────────────────────┘


┌─────────────────────┐
│   Admin Panel       │
│     (React)         │
└──────────┬──────────┘
           │ PUT /api/sections/{key} + JWT
           ▼
┌─────────────────────┐
│  AuthMiddleware     │
│  (valida JWT)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  RoleMiddleware     │
│  (edit_content)     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ SectionController   │
│    (update)         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  SectionService     │
│  (validación JSON)  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐     ┌─────────────────────┐
│  Section Model      │────▶│    MySQL            │
│  (upsert)           │     │  UPDATE/INSERT      │
└─────────────────────┘     └─────────────────────┘
```

## 🧪 PRUEBAS

### 1. Instalar schema:
```bash
mysql -u root -p sitra_web < backend/database_sections.sql
```

### 2. Probar endpoint público:
```bash
curl http://localhost/sitra_web/backend/public/api/public/sections
```

### 3. Probar endpoint admin:
```bash
# Login
TOKEN=$(curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sitracabana.org","password":"admin123"}' \
  | jq -r '.data.token')

# Listar secciones
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/sitra_web/backend/public/api/sections

# Actualizar hero
curl -X PUT http://localhost/sitra_web/backend/public/api/sections/hero \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"content":{"title":"Nuevo título"}}'
```

## ✅ CARACTERÍSTICAS

✅ **Headless CMS** - Contenido separado de presentación
✅ **JSON Flexible** - Cualquier estructura de datos
✅ **Auto-create** - PUT crea si no existe
✅ **Validación robusta** - Keys y JSON validados
✅ **Dual API** - Pública (read) + Admin (write)
✅ **RBAC integrado** - Usa permisos de Módulo 2
✅ **Sin hardcoded keys** - Section keys dinámicos
✅ **Escalable** - Fácil agregar más secciones

## 🎨 EJEMPLOS DE CONTENIDO

### Hero:
```json
{
  "title": "SITRACABAÑA",
  "subtitle": "Sindicato de Trabajadores",
  "description": "Defendiendo tus derechos",
  "cta_primary": "Afíliate",
  "stats": [...]
}
```

### About:
```json
{
  "title": "Nosotros",
  "mission": {"title": "...", "content": "..."},
  "vision": {"title": "...", "content": "..."},
  "history": {"title": "...", "content": "..."}
}
```

### Contact:
```json
{
  "email": "contacto@sitracabana.org",
  "phone": "+502 0000-0000",
  "social": {
    "facebook": "...",
    "twitter": "..."
  }
}
```

## 🚀 PRÓXIMOS MÓDULOS

El CMS está listo como base para:
- ✅ Módulo 4: Noticias (tabla `news`, similar a sections)
- ✅ Módulo 5: Galería (tabla `gallery`, con upload de imágenes)
- ✅ Módulo 6: Documentos (tabla `documents`, con PDFs)
- ✅ Módulo 7: Directiva (tabla `board_members`)

## 🎊 CONCLUSIÓN

**Módulo 3 entregado y testeado.**

El CMS headless está:
- ✅ Funcionando correctamente
- ✅ Integrado con JWT y RBAC
- ✅ Documentado completamente
- ✅ Listo para landing React
- ✅ Listo para admin panel React
- ✅ Preparado para extensión

---

**Total de módulos implementados: 3/7**
1. ✅ Autenticación JWT
2. ✅ RBAC (Roles y Permisos)
3. ✅ CMS (Sections)
