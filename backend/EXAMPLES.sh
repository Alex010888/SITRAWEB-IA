# ===================================
# EJEMPLOS DE USO - SITRACABAÑA API
# ===================================

# Nota: Reemplaza localhost/sitra_web por tu dominio en producción

BASE_URL="http://localhost/sitra_web/backend/public/api"

# ===================================
# 1. LOGIN
# ===================================

curl -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@sitracabana.org",
    "password": "admin123"
  }'

# Respuesta esperada:
# {
#   "success": true,
#   "message": "Login exitoso",
#   "data": {
#     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI...",
#     "user": {
#       "id": 1,
#       "nombre": "Administrador",
#       "email": "admin@sitracabana.org",
#       "rol": "superadmin"
#     }
#   }
# }

# Guardar el token:
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI..."

# ===================================
# 2. OBTENER USUARIO ACTUAL
# ===================================

curl -X GET "${BASE_URL}/auth/me" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# 3. LISTAR USUARIOS
# ===================================

curl -X GET "${BASE_URL}/users" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# 4. CREAR USUARIO (solo superadmin)
# ===================================

curl -X POST "${BASE_URL}/users" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "password": "secreto123",
    "rol": "editor",
    "activo": 1
  }'

# ===================================
# 5. OBTENER USUARIO POR ID
# ===================================

curl -X GET "${BASE_URL}/users/2" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# 6. ACTUALIZAR USUARIO
# ===================================

curl -X PUT "${BASE_URL}/users/2" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "nombre": "Juan Actualizado",
    "rol": "directivo"
  }'

# ===================================
# 7. DESACTIVAR USUARIO
# ===================================

curl -X DELETE "${BASE_URL}/users/2" \
  -H "Authorization: Bearer ${TOKEN}"

# ===================================
# 8. LOGOUT
# ===================================

curl -X POST "${BASE_URL}/auth/logout"

# ===================================
# ERRORES COMUNES
# ===================================

# 401 - Token inválido o expirado:
# {
#   "success": false,
#   "message": "Token inválido o expirado"
# }

# 403 - Sin permisos:
# {
#   "success": false,
#   "message": "No tienes permisos suficientes"
# }

# 400 - Email duplicado:
# {
#   "success": false,
#   "message": "El email ya está registrado"
# }
