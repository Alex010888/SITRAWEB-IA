# Desplegar SITRACABAÑA en Render

Guía para publicar el proyecto en [Render](https://render.com) usando el Blueprint incluido.

## Requisitos previos

1. **Cuenta en Render**: https://dashboard.render.com
2. **Repositorio Git** (GitHub, GitLab o Bitbucket) con el código del proyecto
3. **Índice FAISS del Defensor** en el repo:
   - Carpeta: `Defensor Laboral IA/rag_sitracabana/data/index/`
   - Archivos: `faiss.index` y `meta.jsonl`
   - Si no existen, generarlos en local y hacer commit:
     ```bash
     cd "Defensor Laboral IA/rag_sitracabana"
     python scripts/04_build_faiss_index.py
     ```

## Pasos para desplegar

### 1. Conectar el repositorio

1. En el **Dashboard de Render** → **New** → **Blueprint**.
2. Conecta el repositorio que contiene este proyecto (donde está `render.yaml`).
3. Render detectará el Blueprint y mostrará los servicios y la base de datos.

### 2. Configurar variables secretas

Al crear el Blueprint:

- **JWT_SECRET**: se genera automáticamente; no hace falta introducirlo.
- El Defensor en Render usa **modo standalone** (solo búsqueda semántica con FAISS, sin LLM). En local el proyecto usa **Ollama** como LLM; en la nube no se requiere OpenAI ni API key.

### 3. Crear el Blueprint

Pulsa **Apply** para crear:

- **sitra-web**: API backend (PHP) en Docker.
- **sitra-defensor**: API del Defensor Laboral IA (Python).
- **sitra-frontend**: sitio estático del frontend (React/Vite).
- **sitra-db**: base de datos PostgreSQL (plan free).

### 4. Inicializar la base de datos

Tras el primer despliegue, crea las tablas en Postgres:

1. En el Dashboard, entra a la base de datos **sitra-db**.
2. Abre **Connect** y copia el **External Database URL** (o usa la consola de Render).
3. Ejecuta el script SQL de esquema:
   - Archivo: `backend/database_render_postgres.sql`
   - Puedes ejecutarlo desde la pestaña **Shell** de la base de datos en Render, o con `psql` local usando la URL de conexión.

Con eso se crean las tablas `usuarios`, `sections`, `media`, `activity_logs` y un usuario admin por defecto (email: `admin@sitracabana.org`, contraseña: `admin123`).

## URLs tras el despliegue

| Servicio   | URL |
|-----------|-----|
| **API (backend)** | `https://sitra-web.onrender.com` |
| **Frontend**      | `https://sitra-frontend.onrender.com` |
| **Defensor IA**    | `https://sitra-defensor.onrender.com` |

El frontend ya está configurado para usar la API en `https://sitra-web.onrender.com/api`. Si cambias el nombre del servicio web en Render, actualiza en **sitra-frontend** la variable de entorno **VITE_API_URL** y vuelve a desplegar el frontend.

## Variables de entorno (resumen)

| Servicio        | Variable        | Origen / Uso |
|-----------------|-----------------|--------------|
| sitra-web       | DATABASE_URL    | Desde Blueprint (Postgres sitra-db). |
| sitra-web       | JWT_SECRET      | Generada por Render. |
| sitra-web       | DEFENSOR_HOST / DEFENSOR_PORT | Red interna (sitra-defensor). |
| sitra-defensor  | —               | Sin LLM en Render (Ollama es para uso local). |
| sitra-frontend  | VITE_API_URL    | `https://sitra-web.onrender.com/api` (ajustar si cambias el nombre del servicio). |

## Notas

- **Plan free**: los servicios pueden hibernar tras inactividad; la primera petición puede tardar más.
- **API**: se sirve solo el backend (Dockerfile `docker/Dockerfile.render.api`); la raíz del servicio es la API (rutas bajo `/api/...`).
- **Base de datos**: se usa PostgreSQL de Render; el backend soporta `DATABASE_URL` además de `DB_*` para MySQL.
- **Defensor**: en Render corre `app_standalone.py` (solo FAISS, búsqueda semántica). En local el proyecto usa **Ollama** como LLM (sin OpenAI); en la nube no se usa LLM por defecto.

## Solución de problemas

- **Defensor no arranca / "faiss.index not found"**: asegúrate de que `data/index/` (con `faiss.index` y `meta.jsonl`) esté en el repo dentro de `Defensor Laboral IA/rag_sitracabana/`.
- **Frontend no ve el API**: comprueba que **VITE_API_URL** en sitra-frontend sea la URL pública del backend (sin barra final) y vuelve a desplegar el frontend.
- **Error de base de datos**: verifica que hayas ejecutado `backend/database_render_postgres.sql` en la base de datos **sitra-db**.
- **Login no funciona**: después de ejecutar el SQL, usa `admin@sitracabana.org` / `admin123` o crea un usuario desde el panel una vez hayas entrado.
