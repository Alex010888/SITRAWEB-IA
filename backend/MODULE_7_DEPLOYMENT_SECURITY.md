# Módulo 7: Deployment & Security Hardening

Plan de despliegue y endurecimiento de seguridad para el sistema (API PHP, MySQL, React Admin, Landing). Sin lógica de negocio: solo configuración, servidor y buenas prácticas.

---

## 1. Variables de entorno (backend)

- **Uso:** Secretos y configuración por entorno en `.env` (nunca en código).
- **Permisos recomendados:** `chmod 600 backend/.env`.
- **Producción:** Copiar `deployment/.env.production.example` a `backend/.env`, rellenar y no subir el archivo real al repositorio.
- **Contenido mínimo:** `DB_*`, `JWT_SECRET`, `API_DEBUG=false`, `CORS_ALLOWED_ORIGINS` (orígenes permitidos separados por coma).

---

## 2. Configuración del servidor web

### 2.1 Apache (.htaccess)

- **DocumentRoot:** Debe ser `backend/public` (solo `index.php` y `.htaccess` expuestos).
- **Raíz del backend** (si algo apunta a `backend/`): Usar `deployment/.htaccess.root.example` para denegar `.env`, `.git`, `*.sql`, listado.
- **backend/public:** Ver `deployment/htaccess.public.example` (rewrite a `index.php`, pasar `Authorization`, opcionalmente cabeceras de seguridad).
- **backend/uploads:** Ya existe `.htaccess` que desactiva ejecución de PHP y deniega `*.php|*.phar`. Mantener.

### 2.2 Nginx

- Ver `deployment/nginx.conf.example`.
- Reglas clave: `root` = `backend/public`; bloquear `\.env`, `\.git`, `\.sql`; servir `/uploads` como estáticos sin ejecutar PHP; pasar `HTTP_AUTHORIZATION` a PHP-FPM; cabeceras X-Frame-Options, X-Content-Type-Options, Referrer-Policy.

---

## 3. Configuración PHP (producción)

- **display_errors:** `Off` (el entry point ya usa `ini_set('display_errors', '0')`; asegurarlo también en `php.ini`).
- **log_errors:** `On`; **error_log** apuntando a un archivo o syslog.
- **memory_limit:** Valor razonable (ej. 128M o 256M).
- **disable_functions:** Valorar desactivar en entornos restrictivos: `exec`, `passthru`, `shell_exec`, `system`, `eval`, etc., según necesidad.
- **expose_php:** `Off`.
- **session:** Si no se usan sesiones, no es crítico; si se usan, `session.cookie_httponly = 1`, `session.cookie_secure = 1` en HTTPS.
- **API_DEBUG:** Debe ser `false` en producción (controlado por `.env`).

---

## 4. CORS y cabeceras de seguridad (API)

- **CORS:** En producción definir `CORS_ALLOWED_ORIGINS` en `.env` (lista de orígenes permitidos separada por coma). Si está vacío, la API usa `*` (solo para desarrollo).
- **Cabeceras enviadas por la API:** X-Content-Type-Options, X-Frame-Options, Referrer-Policy, X-XSS-Protection (aplicadas en el router).
- **Content-Security-Policy:** Opcional; para una API JSON suele no ser estricta; si se añade, configurar según necesidades del frontend.

---

## 5. JWT

- **Secreto:** Fuerte y aleatorio (mínimo 32 caracteres); distinto por entorno.
- **Expiración:** `JWT_EXPIRATION` razonable (ej. 3600 segundos); rechazar tokens expirados (ya implementado en el backend).
- **Validación:** Siempre validar firma y expiración; no confiar en el payload sin verificar.

---

## 6. Base de datos

- **Usuario:** No usar `root` en producción; crear un usuario con privilegios mínimos (SELECT, INSERT, UPDATE, DELETE en las tablas de la aplicación).
- **Conexión:** Solo desde el servidor de aplicación; no exponer MySQL a internet salvo si es estrictamente necesario.
- **Respaldos:** Cifrados (ej. `openssl` o herramienta de backup que soporte cifrado); almacenar en un lugar seguro fuera del servidor web.
- **Frecuencia:** Según criticidad (diario mínimo recomendado; retención según política).

---

## 7. Frontend (React)

- **Admin Panel:** Rutas protegidas; UI según rol; token en memoria/localStorage, nunca en URL; cierre de sesión automático en 401 (ya implementado en el cliente).
- **Landing pública:** Acceso solo lectura a la API (endpoints públicos); considerar rate limiting en el servidor o proxy para evitar abuso.

---

## 8. Estrategia de despliegue

### 8.1 Backend (PHP)

- **Ubicación:** VPS o hosting con PHP 8+ y MySQL.
- **DocumentRoot:** `backend/public`.
- **Carpetas fuera de la raíz web:** `app/`, `routes/`, `uploads/` (uploads puede estar bajo `backend/` y servirse por alias/virtual si se desea).
- **Pasos:** Subir código; configurar `.env`; asegurar permisos de escritura en `backend/uploads`; apuntar el servidor web a `backend/public`.

### 8.2 Frontend (React)

- **Build:** `npm run build` (Admin y Landing).
- **Opciones:** Servir los estáticos con Nginx/Apache; o desplegar en Vercel/Netlify con variables de entorno para la URL de la API.
- **API URL:** Configurar en build (ej. `VITE_API_BASE_URL`) hacia la URL pública de la API.

---

## 9. Docker (opcional)

- **Archivos:** `backend/deployment/docker-compose.yml`, `Dockerfile.api`, `nginx.docker.conf`, `.env.docker.example`.
- **Servicios:** PHP-FPM (api), Nginx, MySQL; `.env` separado para Docker (no reutilizar el de producción real).
- **Volúmenes:** `backend/uploads` persistente; código montado o copiado según el ejemplo.
- **Uso:** `cd backend && docker compose -f deployment/docker-compose.yml up -d` (ajustar según ubicación del compose).

---

## 10. Monitoreo y recuperación

- **Logs de errores:** PHP `error_log`; Nginx/Apache access/error logs; revisión periódica.
- **Activity logs:** Módulo 6 (auditoría); revisar para detectar acciones anómalas.
- **Respaldos:** Base de datos y, si se considera necesario, `backend/uploads`; automatizar (cron) y documentar la restauración.
- **Restauración:** Documentar pasos: restaurar BD desde dump; reemplazar archivos si aplica; verificar `.env` y permisos; comprobar salud de la API.

---

## 11. Checklist de despliegue

- [ ] DocumentRoot = `backend/public`.
- [ ] `.env` en producción con valores reales; permisos 600; no en el repo.
- [ ] `API_DEBUG=false`.
- [ ] `CORS_ALLOWED_ORIGINS` definido con los orígenes del admin y landing.
- [ ] `JWT_SECRET` fuerte y único.
- [ ] Usuario MySQL con privilegios mínimos (no root).
- [ ] PHP `display_errors=Off`, `log_errors=On`.
- [ ] Uploads: escritura para el usuario del servidor web; sin ejecución de PHP.
- [ ] Frontend: build con URL de API correcta; sin tokens en URL.
- [ ] HTTPS en producción (configurar en Nginx/Apache o en el proxy).

---

## 12. Checklist de seguridad

- [ ] No hay secretos en el código ni en el repositorio.
- [ ] `.env` y archivos sensibles no accesibles por el servidor web (.htaccess / Nginx).
- [ ] Listado de directorios desactivado.
- [ ] PHP no se ejecuta en `uploads`.
- [ ] CORS restrictivo en producción.
- [ ] Cabeceras de seguridad (X-Frame-Options, X-Content-Type-Options, Referrer-Policy) aplicadas.
- [ ] JWT con secreto fuerte y expiración.
- [ ] Base de datos: usuario con menos privilegios; respaldos cifrados.
- [ ] Logs de auditoría (Módulo 6) revisables.
- [ ] Rate limiting o WAF valorado para endpoints públicos.

---

## 13. Plan de respaldo y recuperación

### 13.1 Respaldos

- **Base de datos:** Dump periódico (ej. diario) con `mysqldump`; cifrar (ej. `openssl enc`) y guardar fuera del servidor.
- **Archivos:** Opcional: `backend/uploads` y, si se desea, `backend/.env` (en lugar seguro); código puede reconstruirse desde el repositorio.
- **Automatización:** Cron que ejecute el dump y, si aplica, copia de uploads; rotación y retención según política.

### 13.2 Restauración

1. Restaurar la base de datos desde el dump (desencriptar si aplica; `mysql < backup.sql` o importación desde cliente).
2. Verificar que `backend/.env` exista y sea correcto.
3. Asegurar permisos en `backend/uploads` (usuario del servidor web).
4. Reiniciar PHP-FPM / servidor web si es necesario.
5. Comprobar salud de la API (login, un endpoint protegido y uno público).
6. Revisar logs de errores tras la restauración.

---

## 14. Referencia de archivos de despliegue

| Archivo | Descripción |
|--------|-------------|
| `deployment/.htaccess.root.example` | Protección raíz backend (denegar .env, .git, etc.). |
| `deployment/htaccess.public.example` | Ejemplo .htaccess para `backend/public`. |
| `deployment/nginx.conf.example` | Ejemplo Nginx para API y uploads. |
| `deployment/docker-compose.yml` | Docker: API, Nginx, MySQL. |
| `deployment/Dockerfile.api` | Imagen PHP-FPM para la API. |
| `deployment/nginx.docker.conf` | Nginx para entorno Docker. |
| `deployment/.env.docker.example` | Variables de entorno para Docker. |
| `deployment/.env.production.example` | Variables de entorno para producción. |
