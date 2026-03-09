# SITRACABAÑA - Levantar proyecto con Docker Desktop

## Requisitos

- **Docker Desktop** instalado y en ejecución.
- Índice FAISS del Defensor ya generado en:  
  `Defensor Laboral IA/rag_sitracabana/data/index/`  
  (ejecutar antes `scripts/04_build_faiss_index.py` en ese directorio si no existe).

## Opción 1: GUI (recomendada)

1. Abre Docker Desktop.
2. En la raíz del proyecto ejecuta:
   ```bash
   python levantar_docker_gui.py
   ```
3. Pulsa **"Levantar proyecto"**. La primera vez construye las imágenes (puede tardar).
4. Para detener: **"Detener proyecto"**.

## Opción 2: Línea de comandos

Desde la raíz del proyecto (`sitra_web`):

```bash
# Levantar todo
docker compose up -d --build

# Ver estado
docker compose ps

# Ver logs
docker compose logs -f

# Detener
docker compose down
```

## URLs

| Servicio   | URL                     |
|-----------|-------------------------|
| Sitio PHP | http://localhost:8080  |
| Frontend  | http://localhost:3000  |
| Defensor  | http://localhost:5000  |

## API key del Defensor (LangChain)

Para que el asistente use OpenAI/Groq dentro del contenedor:

- Crea `.env.docker` en la raíz con:  
  `DEFENSOR_LLM_API_KEY=sk-tu-api-key`
- En `docker-compose.yml`, en el servicio `defensor`, descomenta la línea  
  `# env_file: [.env.docker]` y déjala como `env_file: [.env.docker]`.

O pasa la variable al levantar:

```bash
set DEFENSOR_LLM_API_KEY=sk-tu-key
docker compose up -d
```

## Solución de problemas

- **"Cannot connect to Docker"**: inicia Docker Desktop.
- **Defensor no arranca / "faiss.index not found"**: genera el índice en el host:
  ```bash
  cd "Defensor Laboral IA\rag_sitracabana"
  python scripts/04_build_faiss_index.py
  ```
  Luego vuelve a levantar con la GUI o `docker compose up -d`.
