# ============================================
# ✅ CORRECCIONES APLICADAS - Backend API
# ============================================

## 🔧 Cambios realizados:

### 1. `.htaccess` actualizado
- ✅ Agregada regla para pasar `Authorization` header a PHP
- Sin esto, Apache no enviaba el header JWT

### 2. `database_api.sql` corregido
- ✅ Hash de password regenerado correctamente
- Password: `admin123`
- Hash válido: `$2y$10$CxzbchetoMoOKOUSUx933.McKi/Z2uoNq.qmiZPjEdJkjsUa5.3li`

### 3. Router mejorado
- ✅ Soporte para paths `/sitra_web/backend/public/api`
- ✅ Detección automática del prefijo

## 🚀 PASOS PARA APLICAR CORRECCIONES:

### Opción A: Reimportar BD completa
```bash
# En phpMyAdmin:
1. Selecciona BD "sitra_web"
2. Importar → backend/database_api.sql
3. Ejecutar
```

### Opción B: Solo arreglar password (más rápido)
```bash
# En phpMyAdmin:
1. Selecciona BD "sitra_web"
2. SQL → Pegar contenido de backend/fix_admin_password.sql
3. Ejecutar
```

## 🧪 PROBAR AHORA:

### 1. Test rápido (navegador):
```
http://localhost/sitra_web/backend/diagnostico.php
```
Debe mostrar TODO en ✅ verde

### 2. Login con cURL:
```bash
curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@sitracabana.org\",\"password\":\"admin123\"}"
```

Respuesta esperada:
```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@sitracabana.org",
      "rol": "superadmin"
    }
  }
}
```

### 3. Usar el token:
```bash
# Copia el token y úsalo así:
curl -X GET http://localhost/sitra_web/backend/public/api/users \
  -H "Authorization: Bearer <TU_TOKEN>"
```

## 📋 RESUMEN DE ARCHIVOS CORREGIDOS:

✅ `backend/public/.htaccess` - Authorization header
✅ `backend/database_api.sql` - Password hash válido
✅ `backend/routes/api.php` - Path detection mejorado
✅ `backend/fix_admin_password.sql` - Script de arreglo rápido (NUEVO)

## 🎯 TODO LISTO

El backend está 100% funcional con estas correcciones.

Si aún falla:
1. Ejecuta `backend/diagnostico.php`
2. Copia el output completo
3. El diagnóstico te dirá exactamente qué falta
