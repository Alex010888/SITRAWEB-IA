# Desplegar SITRACABAÑA en Render

Pasos para publicar el proyecto en [Render](https://render.com) usando el Blueprint incluido.

## Requisitos previos

1. **Cuenta en Render** (https://dashboard.render.com).
2. **Repositorio Git** (GitHub, GitLab o Bitbucket) con el código del proyecto.
3. **Índice FAISS del Defensor** en el repo:
   - Carpeta: `Defensor Laboral IA/rag_sitracabana/data/index/`
   - Archivos: `faiss.index` y `meta.jsonl`
   - Si no existen, generarlos en local con:
     ```bash
     cd "Defensor Laboral IA/rag_sitracabana"
     python scripts/04_build_faiss_index.py
     ```
     y hacer commit de `data/index/`.

## Despliegue con Blueprint

1. En el **Dashboard de Render**, entra a tu cuenta y abre el workspace.
2. **New** → **Blueprint**.
3. Conecta el repositorio que contiene este proyecto (donde está `render.yaml`).
4. Render detectará `render.yaml` y mostrará los servicios:
   - **sitra-web**: backend PHP (Docker).
   - **sitra-defensor**: API del Defensor Laboral IA (Python).
   - **sitra-frontend**: sitio estático del frontend (React/Vite).
5. Cuando te pida **OPENAI_API_KEY** (Defensor), introduce tu API key de OpenAI (o la que uses para el LLM).
6. Crea el Blueprint. Render construirá y desplegará los tres servicios.

## URLs tras el despliegue

- **Sitio PHP (backend):** `https://sitra-web.onrender.com`
- **Frontend:** `https://sitra-frontend.onrender.com`
- **Defensor (API):** `https://sitra-defensor.onrender.com`

El frontend está configurado para llamar al backend en `https://sitra-web.onrender.com`. Si cambias el nombre del servicio web en Render, actualiza en el servicio **sitra-frontend** la variable de entorno **VITE_API_URL** (y vuelve a desplegar el frontend para que se regenere el build).

## Variables de entorno importantes

| Servicio        | Variable           | Uso |
|-----------------|--------------------|-----|
| sitra-web      | DEFENSOR_HOST / DEFENSOR_PORT | Se rellenan desde el Blueprint (red interna con sitra-defensor). |
| sitra-defensor | OPENAI_API_KEY     | API key de OpenAI (o la que use tu LLM). Se pide al crear el Blueprint. |
| sitra-frontend | VITE_API_URL       | URL pública del backend (por defecto `https://sitra-web.onrender.com/backend/public/api`). |

## Notas

- **Plan free:** los servicios pueden hibernar tras inactividad; la primera petición puede tardar más.
- **PHP:** el backend usa el servidor integrado de PHP escuchando en el `PORT` que asigna Render.
- **Defensor:** usa `app_standalone.py` (FAISS + búsqueda semántica). Para usar LangChain + LLM, configura `OPENAI_API_KEY` y, si quieres, cambia el `startCommand` en el Blueprint a `python app_langchain.py` (y asegúrate de que las dependencias LangChain estén en `requirements.txt`).
- **Base de datos:** este Blueprint no incluye base de datos. Si la app PHP usa MySQL/Postgres, crea una base en Render y configura en el servicio **sitra-web** las variables que lea tu `config/database.php` (o equivalente).

## Solución de problemas

- **Defensor no arranca / "faiss.index not found"**: asegúrate de que `data/index/` (con `faiss.index` y `meta.jsonl`) esté en el repo y dentro de `Defensor Laboral IA/rag_sitracabana/`.
- **Frontend no ve el API**: revisa que **VITE_API_URL** en sitra-frontend sea la URL pública correcta del backend y que hayas vuelto a desplegar el frontend después de cambiarla.
- **PHP no conecta al Defensor**: en Render, sitra-web usa red interna (DEFENSOR_HOST / DEFENSOR_PORT) hacia sitra-defensor. No hace falta configurar la URL pública del Defensor para el backend.
