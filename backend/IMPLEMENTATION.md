# ============================================
# MÓDULO 1: AUTENTICACIÓN Y USUARIOS - COMPLETADO ✅
# ============================================

## 📦 ARCHIVOS GENERADOS

backend/
├── app/
│   ├── config/
│   │   ├── database.php       # Conexión PDO Singleton
│   │   └── env.php            # Loader de .env
│   ├── controllers/
│   │   ├── AuthController.php # Login, Logout, Me
│   │   └── UserController.php # CRUD Usuarios
│   ├── middlewares/
│   │   └── AuthMiddleware.php # Validación JWT
│   ├── models/
│   │   └── User.php           # Modelo Usuario (PDO)
│   └── services/
│       └── JwtService.php     # JWT nativo (HS256)
├── routes/
│   └── api.php                # Router + Endpoints
├── public/
│   ├── index.php              # Entry point
│   └── .htaccess              # Rutas limpias
├── .env                       # Variables (configurado)
├── .env.example               # Template
├── database_api.sql           # Schema + usuario de prueba
├── README.md                  # Documentación completa
├── EXAMPLES.sh                # Ejemplos cURL
├── postman_collection.json    # Colección Postman
└── test_api.php               # Script de test rápido

## 🚀 PASOS PARA EJECUTAR

### 1. Importar Base de Datos

```bash
# En phpMyAdmin o terminal:
mysql -u root -p < backend/database_api.sql
```

O desde phpMyAdmin:
- Abre http://localhost/phpmyadmin
- Selecciona BD `sitra_web`
- Ve a "Importar"
- Sube `backend/database_api.sql`

### 2. Verificar .env

Archivo: `backend/.env`

```env
DB_HOST=127.0.0.1
DB_NAME=sitra_web
DB_USER=root
DB_PASS=

JWT_SECRET=sitracabana_jwt_secret_key_2026_change_in_production
JWT_EXPIRATION=3600
API_DEBUG=true
```

### 3. Test Rápido

Abre en navegador:
```
http://localhost/sitra_web/backend/test_api.php
```

Debe mostrar:
✅ Login exitoso
✅ Usuarios listados correctamente

## 📡 ENDPOINTS IMPLEMENTADOS

### 🔓 Públicos

- `POST /api/auth/login`    - Login (obtener JWT)
- `POST /api/auth/logout`   - Logout (client-side)

### 🔒 Protegidos (requieren JWT)

- `GET  /api/auth/me`       - Obtener usuario actual
- `GET  /api/users`         - Listar todos los usuarios
- `GET  /api/users/{id}`    - Obtener usuario por ID
- `POST /api/users`         - Crear usuario (superadmin)
- `PUT  /api/users/{id}`    - Actualizar usuario (superadmin)
- `DELETE /api/users/{id}`  - Desactivar usuario (superadmin)

## 🧪 EJEMPLO DE USO (cURL)

```bash
# 1. Login
curl -X POST http://localhost/sitra_web/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sitracabana.org","password":"admin123"}'

# Respuesta:
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

# 2. Listar usuarios (con token)
curl -X GET http://localhost/sitra_web/backend/public/api/users \
  -H "Authorization: Bearer <TU_TOKEN>"
```

## 🔐 CREDENCIALES DE PRUEBA

- **Email:** admin@sitracabana.org
- **Password:** admin123
- **Rol:** superadmin

## ✅ CARACTERÍSTICAS IMPLEMENTADAS

✅ Login con JWT (HS256)
✅ Logout (client-side)
✅ CRUD usuarios completo
✅ Middleware de autenticación
✅ Control de roles (superadmin/directivo/editor)
✅ PDO con prepared statements
✅ Validación de inputs
✅ Hash de passwords (bcrypt)
✅ .env loader (sin Composer)
✅ Manejo centralizado de errores
✅ CORS para desarrollo
✅ Códigos HTTP correctos
✅ Estructura escalable

## 🔒 SEGURIDAD

- Passwords hasheados con `password_hash()` (bcrypt)
- JWT firmado con HMAC-SHA256
- PDO prepared statements (previene SQL injection)
- Validación de inputs
- Token expiration (configurable)
- Role-based access control
- Soft delete de usuarios

## 📋 ROLES DISPONIBLES

- **superadmin** - Acceso total, gestión de usuarios
- **directivo** - Gestión de contenidos (preparado)
- **editor** - Edición básica (preparado)

## 🎯 LISTO PARA INTEGRAR

El módulo está 100% funcional y listo para:

- Conectar con un admin panel React
- Agregar futuros módulos (noticias, galería, etc.)
- Desplegar en producción (cambiar JWT_SECRET)

## 📝 PRÓXIMOS PASOS SUGERIDOS

1. Probar todos los endpoints con Postman (importar `postman_collection.json`)
2. Cambiar `JWT_SECRET` en producción
3. Ajustar CORS según dominio de frontend
4. Implementar refresh tokens (opcional)
5. Agregar rate limiting (opcional)

## 🛠️ TROUBLESHOOTING

### "Database connection error"
- Verifica credenciales en `.env`
- Asegúrate que la BD `sitra_web` existe

### "Token no proporcionado"
- Verifica header: `Authorization: Bearer <token>`
- Apache puede no pasar el header. Agrega en `.htaccess`:
  ```apache
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
  ```

### 404 en todos los endpoints
- Verifica `mod_rewrite` habilitado
- Asegúrate que `.htaccess` está en `/backend/public/`

## 📄 LICENCIA

Proyecto privado - SITRACABAÑA © 2026
