# ===================================
# EJEMPLOS - Módulo 2: RBAC
# ===================================

BASE_URL="http://localhost/sitra_web/backend/public/api"

# ===================================
# 1. LOGIN (obtener token)
# ===================================

curl -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@sitracabana.org",
    "password": "admin123"
  }'

# Guardar el token de la respuesta:
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI..."

# ===================================
# 2. OBTENER PERMISOS DEL USUARIO ACTUAL
# ===================================

curl -X GET "${BASE_URL}/permissions/me" \
  -H "Authorization: Bearer ${TOKEN}"

# Respuesta:
# {
#   "success": true,
#   "data": {
#     "rol": "superadmin",
#     "permissions": ["view_content", "edit_content", "delete_content", ...],
#     "can": {
#       "view_content": true,
#       "edit_content": true,
#       "delete_content": true,
#       "manage_users": true
#     }
#   }
# }

# ===================================
# 3. LISTAR ROLES Y PERMISOS (solo superadmin)
# ===================================

curl -X GET "${BASE_URL}/permissions/roles" \
  -H "Authorization: Bearer ${TOKEN}"

# Respuesta:
# {
#   "success": true,
#   "data": {
#     "roles": ["superadmin", "directivo", "editor"],
#     "permissions": {
#       "superadmin": ["view_content", "edit_content", ...],
#       "directivo": ["view_content", "edit_content", "view_analytics"],
#       "editor": ["edit_content"]
#     }
#   }
# }

# ===================================
# 4. CREAR USUARIO (requiere manage_users)
# ===================================

# ✅ Con superadmin (tiene manage_users):
curl -X POST "${BASE_URL}/users" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Editor Test",
    "email": "editor@example.com",
    "password": "password123",
    "rol": "editor",
    "activo": 1
  }'

# ❌ Con editor (NO tiene manage_users):
# Respuesta 403:
# {
#   "success": false,
#   "message": "No tienes permiso para: manage_users",
#   "error": "forbidden"
# }

# ===================================
# 5. ACTUALIZAR USUARIO (requiere manage_users)
# ===================================

curl -X PUT "${BASE_URL}/users/2" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Editor Actualizado",
    "rol": "directivo"
  }'

# ===================================
# 6. ELIMINAR USUARIO (requiere manage_users)
# ===================================

curl -X DELETE "${BASE_URL}/users/2" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# ESCENARIOS DE PRUEBA
# ===================================

# Escenario 1: superadmin puede todo
# - Crear usuario ✅
# - Editar usuario ✅
# - Eliminar usuario ✅
# - Ver roles ✅

# Escenario 2: directivo puede ver y editar contenidos
# - Ver contenidos ✅
# - Editar contenidos ✅
# - Eliminar contenidos ❌ (403)
# - Crear usuarios ❌ (403)

# Escenario 3: editor solo puede editar
# - Editar contenidos ✅
# - Ver contenidos ❌ (403)
# - Crear usuarios ❌ (403)

# ===================================
# CÓDIGOS HTTP
# ===================================

# 200 - OK
# 201 - Created
# 400 - Bad Request
# 401 - Unauthorized (token inválido)
# 403 - Forbidden (sin permiso)
# 404 - Not Found
# 500 - Internal Server Error
