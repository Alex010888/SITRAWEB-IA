# 🚀 QUICK START - CMS / Sections

Guía rápida para usar el CMS headless en 2 minutos.

## 📌 Lo Básico

**Headless CMS:** Contenido en JSON, API REST, sin frontend acoplado.

**Dos APIs:**
- 🌐 **Pública** - Para landing page (sin auth)
- 🔒 **Admin** - Para panel admin (con JWT + permisos)

## 🗄️ Instalación

```bash
# Crear tabla sections
mysql -u root -p sitra_web < backend/database_sections.sql

# Verificar
curl http://localhost/sitra_web/backend/public/api/public/sections
```

## 🌐 USO PÚBLICO (Landing Page)

### Fetch todas las secciones:

```javascript
fetch('http://localhost/sitra_web/backend/public/api/public/sections')
  .then(res => res.json())
  .then(data => {
    data.data.forEach(section => {
      console.log(section.section_key, section.content);
    });
  });
```

### Fetch una sección específica:

```javascript
fetch('http://localhost/sitra_web/backend/public/api/public/sections/hero')
  .then(res => res.json())
  .then(data => {
    const hero = data.data.content;
    // hero.title, hero.subtitle, etc.
  });
```

### Ejemplo: Renderizar hero

```jsx
// React component
function HeroSection() {
  const [hero, setHero] = useState(null);

  useEffect(() => {
    fetch('/api/public/sections/hero')
      .then(res => res.json())
      .then(data => setHero(data.data.content));
  }, []);

  if (!hero) return <div>Cargando...</div>;

  return (
    <section>
      <h1>{hero.title}</h1>
      <p>{hero.subtitle}</p>
      <p>{hero.description}</p>
    </section>
  );
}
```

## 🔒 USO ADMIN (Panel)

### Listar secciones (admin):

```javascript
fetch('http://localhost/sitra_web/backend/public/api/sections', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
  .then(res => res.json())
  .then(data => {
    // data.data = array de secciones con ID
  });
```

### Actualizar sección:

```javascript
fetch('http://localhost/sitra_web/backend/public/api/sections/hero', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    content: {
      title: 'SITRACABAÑA',
      subtitle: 'Sindicato de Trabajadores',
      description: 'Defendiendo tus derechos laborales'
    }
  })
})
  .then(res => res.json())
  .then(data => {
    console.log('Sección actualizada:', data);
  });
```

### Crear nueva sección:

```javascript
// PUT crea automáticamente si no existe
fetch('http://localhost/sitra_web/backend/public/api/sections/services', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    content: {
      title: 'Nuestros Servicios',
      items: [
        { icon: 'briefcase', title: 'Asesoría Legal' },
        { icon: 'heart', title: 'Apoyo Social' }
      ]
    }
  })
});
```

### Eliminar sección:

```javascript
// Requiere permiso delete_content (solo superadmin)
fetch('http://localhost/sitra_web/backend/public/api/sections/services', {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
  .then(res => res.json())
  .then(data => {
    console.log('Sección eliminada');
  });
```

## 📝 ESTRUCTURA DE CONTENIDO

El `content` es **JSON libre**. Ejemplos:

### Hero simple:
```json
{
  "title": "SITRACABAÑA",
  "subtitle": "Sindicato de Trabajadores",
  "description": "...",
  "cta": "Afíliate"
}
```

### Hero con stats:
```json
{
  "title": "SITRACABAÑA",
  "stats": [
    { "icon": "shield", "title": "Defensa", "value": "100%" },
    { "icon": "people", "title": "Unidad", "value": "500+" }
  ]
}
```

### About con subsecciones:
```json
{
  "title": "Nosotros",
  "mission": {
    "title": "Misión",
    "content": "Representar y proteger..."
  },
  "vision": {
    "title": "Visión",
    "content": "Ser un sindicato moderno..."
  }
}
```

## 🎯 PERMISOS

| Acción | Permiso | Rol |
|--------|---------|-----|
| Ver (público) | ninguno | todos |
| Ver (admin) | `edit_content` | superadmin, directivo, editor |
| Editar | `edit_content` | superadmin, directivo, editor |
| Eliminar | `delete_content` | superadmin |

## 🔍 ENDPOINTS

### Públicos (sin auth):
```
GET /api/public/sections
GET /api/public/sections/{key}
```

### Admin (con JWT):
```
GET    /api/sections
GET    /api/sections/{key}
PUT    /api/sections/{key}
DELETE /api/sections/{key}
```

## 🧪 PRUEBAS RÁPIDAS

```bash
# Público: Obtener hero
curl http://localhost/sitra_web/backend/public/api/public/sections/hero

# Admin: Login
TOKEN=$(curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sitracabana.org","password":"admin123"}' \
  | jq -r '.data.token')

# Admin: Actualizar hero
curl -X PUT http://localhost/sitra_web/backend/public/api/sections/hero \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"content":{"title":"Nuevo título"}}'
```

## 🎨 SECCIONES INCLUIDAS

Por defecto viene con:
- `hero` - Banner principal
- `about` - Misión/Visión/Historia
- `contact` - Datos de contacto
- `footer` - Footer del sitio

Puedes agregar más con PUT.

## 💡 BUENAS PRÁCTICAS

✅ **Section keys descriptivos:** `hero`, `services`, `testimonials`
✅ **JSON estructurado:** Usa objetos/arrays para datos complejos
✅ **Versionado:** Guarda backups del content antes de cambios grandes
✅ **Validación frontend:** Valida que los campos existan antes de renderizar
✅ **Loading states:** Muestra spinners mientras cargas secciones
✅ **Error handling:** Maneja 404 si una sección no existe

## ❌ ERRORES COMUNES

```javascript
// ❌ Enviar content como string
{ "content": "mi texto" }  // INCORRECTO

// ✅ Enviar content como objeto
{ "content": { "text": "mi texto" } }  // CORRECTO
```

```javascript
// ❌ Olvidar el wrapper "content"
fetch('/api/sections/hero', {
  method: 'PUT',
  body: JSON.stringify({ title: 'Título' })  // FALTA "content"
});

// ✅ Con wrapper "content"
fetch('/api/sections/hero', {
  method: 'PUT',
  body: JSON.stringify({
    content: { title: 'Título' }  // CORRECTO
  })
});
```

## 📚 Más Info

- Documentación completa: `MODULE_3_CMS.md`
- Ejemplos cURL: `EXAMPLES_CMS.sh`
- Postman: `postman_collection.json` (v3.0.0)

---

**¿Dudas?** Lee `MODULE_3_CMS.md` para detalles completos.
