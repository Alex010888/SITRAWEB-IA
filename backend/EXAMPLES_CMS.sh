# ===================================
# EJEMPLOS - Módulo 3: CMS/Sections
# ===================================

BASE_URL="http://localhost/sitra_web/backend/public/api"

# ===================================
# 1. ENDPOINTS PÚBLICOS (sin auth)
# ===================================

# Listar todas las secciones (público)
curl -X GET "${BASE_URL}/public/sections"

# Response:
# {
#   "success": true,
#   "data": [
#     {
#       "section_key": "hero",
#       "content": { ... },
#       "updated_at": "2026-02-06 10:00:00"
#     }
#   ]
# }

# Obtener una sección específica (público)
curl -X GET "${BASE_URL}/public/sections/hero"

# Response:
# {
#   "success": true,
#   "data": {
#     "section_key": "hero",
#     "content": {
#       "title": "SITRACABAÑA",
#       "subtitle": "Sindicato de Trabajadores"
#     },
#     "updated_at": "2026-02-06 10:00:00"
#   }
# }

# ===================================
# 2. OBTENER TOKEN (login)
# ===================================

curl -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@sitracabana.org",
    "password": "admin123"
  }'

# Guardar el token:
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI..."

# ===================================
# 3. ENDPOINTS ADMIN (con auth)
# ===================================

# Listar todas las secciones (admin)
curl -X GET "${BASE_URL}/sections" \
  -H "Authorization: Bearer ${TOKEN}"

# Response:
# {
#   "success": true,
#   "data": [
#     {
#       "id": 1,
#       "section_key": "hero",
#       "content": { ... },
#       "updated_at": "2026-02-06 10:00:00"
#     }
#   ]
# }

# Obtener una sección específica (admin)
curl -X GET "${BASE_URL}/sections/hero" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# 4. ACTUALIZAR SECCIÓN (PUT)
# ===================================

# Actualizar sección hero
curl -X PUT "${BASE_URL}/sections/hero" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "SITRACABAÑA",
      "subtitle": "Sindicato de Trabajadores · Ingenio La Cabaña",
      "description": "Defendiendo tus derechos laborales con transparencia",
      "cta_primary": "Afíliate ahora",
      "cta_secondary": "Más información",
      "stats": [
        {
          "icon": "shield-check",
          "title": "Defensa",
          "description": "Acompañamiento legal y laboral"
        },
        {
          "icon": "people",
          "title": "Unidad",
          "description": "Trabajo colectivo y solidario"
        },
        {
          "icon": "journal-check",
          "title": "Gestión",
          "description": "Transparencia y orden administrativo"
        }
      ]
    }
  }'

# Response:
# {
#   "success": true,
#   "message": "Sección actualizada exitosamente",
#   "data": {
#     "id": 1,
#     "section_key": "hero",
#     "content": { ... },
#     "updated_at": "2026-02-06 11:30:00"
#   }
# }

# ===================================
# 5. CREAR NUEVA SECCIÓN (PUT)
# ===================================

# Si la sección no existe, PUT la crea automáticamente
curl -X PUT "${BASE_URL}/sections/services" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "Nuestros Servicios",
      "items": [
        {
          "icon": "briefcase",
          "title": "Asesoría Legal",
          "description": "Consultas y acompañamiento legal"
        },
        {
          "icon": "heart",
          "title": "Apoyo Social",
          "description": "Ayudas y beneficios para afiliados"
        }
      ]
    }
  }'

# ===================================
# 6. ACTUALIZAR SECCIÓN ABOUT
# ===================================

curl -X PUT "${BASE_URL}/sections/about" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "Nosotros",
      "subtitle": "Nuestra razón de ser: servicio, justicia y dignidad",
      "mission": {
        "title": "Misión",
        "content": "Representar y proteger a las y los trabajadores del Ingenio La Cabaña, promoviendo condiciones laborales justas, seguridad, bienestar y respeto a los derechos."
      },
      "vision": {
        "title": "Visión",
        "content": "Ser un sindicato moderno, transparente y participativo, referente por su gestión y por el impacto positivo en la vida de la base trabajadora."
      },
      "history": {
        "title": "Historia",
        "content": "Nacemos de la necesidad de organizarnos para dialogar, negociar y construir acuerdos, priorizando la unidad y el respeto."
      }
    }
  }'

# ===================================
# 7. ACTUALIZAR CONTACTO
# ===================================

curl -X PUT "${BASE_URL}/sections/contact" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "Contacto",
      "email": "contacto@sitracabana.org",
      "phone": "+502 1234-5678",
      "address": "Ingenio La Cabaña, Santa Lucía Cotzumalguapa, Guatemala",
      "social": {
        "facebook": "https://facebook.com/sitracabana",
        "twitter": "https://twitter.com/sitracabana",
        "youtube": "https://youtube.com/@sitracabana",
        "whatsapp": "+502 1234-5678"
      }
    }
  }'

# ===================================
# 8. ELIMINAR SECCIÓN (DELETE)
# ===================================

# Requiere permiso delete_content (solo superadmin)
curl -X DELETE "${BASE_URL}/sections/services" \
  -H "Authorization: Bearer ${TOKEN}"

# Response:
# {
#   "success": true,
#   "message": "Sección eliminada exitosamente"
# }

# ===================================
# 9. ERRORES COMUNES
# ===================================

# ❌ Sin token (401)
curl -X GET "${BASE_URL}/sections"
# Response: {"success": false, "message": "Token no proporcionado"}

# ❌ Sin permiso (403)
curl -X PUT "${BASE_URL}/sections/hero" \
  -H "Authorization: Bearer <TOKEN_DE_EDITOR>" \
  -H "Content-Type: application/json" \
  -d '{"content": {...}}'
# Response: {"success": false, "message": "No tienes permiso para: edit_content"}

# ❌ JSON inválido (400)
curl -X PUT "${BASE_URL}/sections/hero" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"content": "string no permitido"}'
# Response: {"success": false, "message": "El contenido debe ser un objeto JSON válido"}

# ❌ Section key inválido (400)
curl -X GET "${BASE_URL}/sections/invalid key!"
# Response: {"success": false, "message": "section_key solo puede contener letras, números..."}

# ❌ Sección no encontrada (404)
curl -X GET "${BASE_URL}/sections/nonexistent" \
  -H "Authorization: Bearer ${TOKEN}"
# Response: {"success": false, "message": "Sección no encontrada"}

# ===================================
# 10. CÓDIGOS HTTP
# ===================================

# 200 - OK
# 400 - Bad Request (JSON inválido, validación fallida)
# 401 - Unauthorized (sin token o token inválido)
# 403 - Forbidden (sin permiso)
# 404 - Not Found (sección no existe)
# 500 - Internal Server Error

# ===================================
# USO EN FRONTEND (React)
# ===================================

# Fetch público (landing page):
# fetch('http://localhost/sitra_web/backend/public/api/public/sections')
#   .then(res => res.json())
#   .then(data => {
#     data.data.forEach(section => {
#       console.log(section.section_key, section.content);
#     });
#   });

# Fetch admin (panel):
# fetch('http://localhost/sitra_web/backend/public/api/sections', {
#   headers: {
#     'Authorization': `Bearer ${token}`
#   }
# })
#   .then(res => res.json())
#   .then(data => {
#     // Renderizar lista editable
#   });

# Update admin:
# fetch('http://localhost/sitra_web/backend/public/api/sections/hero', {
#   method: 'PUT',
#   headers: {
#     'Authorization': `Bearer ${token}`,
#     'Content-Type': 'application/json'
#   },
#   body: JSON.stringify({
#     content: {
#       title: 'Nuevo título',
#       subtitle: 'Nuevo subtítulo'
#     }
#   })
# })
#   .then(res => res.json())
#   .then(data => {
#     console.log('Sección actualizada:', data);
#   });
