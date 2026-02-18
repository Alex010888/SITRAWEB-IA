# Búsqueda semántica (embeddings)

El Defensor Laboral IA puede usar **búsqueda semántica** con embeddings para captar mejor consultas como:
- "me echaron sin aviso"
- "me corrieron sin causa"
- "me liquidaron y no me pagaron el finiquito"

## Cómo funciona

1. **Chunks** → Se generan embeddings con Sentence Transformers (`all-MiniLM-L6-v2`) y se guardan en un índice FAISS.
2. **Consulta** → Se embebe la pregunta del usuario con el mismo modelo.
3. **Similitud** → Se buscan los chunks más similares (coseno) y se devuelven al PHP.

## Requisitos

- Python 3.10+ con `sentence-transformers`, `faiss-cpu`, `numpy`
- Índice FAISS generado (`scripts/04_build_faiss_index.py`)

## Pasos para activar

### 1. Regenerar índice (si limpiaste chunks)

```bash
cd "Defensor Laboral IA/rag_sitracabana"
python scripts/04_build_faiss_index.py
```

Esto crea `data/index/faiss.index` y `data/index/meta.jsonl` desde `data/chunks/chunks.jsonl`.

### 2. Iniciar el microservicio Python

```bash
# Opción A: con START_ASISTENTE.bat (recomendado)
START_ASISTENTE.bat

# Opción B: manualmente
cd "Defensor Laboral IA/rag_sitracabana"
..\venv\Scripts\activate
python app_standalone.py
```

El servidor queda en **http://127.0.0.1:5000**.

### 3. Configurar PHP

En `config/app.php`:

```php
'defensor_semantic_url' => 'http://127.0.0.1:5000/api/search',
```

Si el microservicio no está disponible, PHP hace **fallback automático** a búsqueda por palabras.

## Desactivar búsqueda semántica

En `config/app.php`:

```php
'defensor_semantic_url' => null,
```

## Endpoints del microservicio

| Endpoint      | Método | Uso                          |
|---------------|--------|------------------------------|
| `/api/search` | POST   | Búsqueda semántica (PHP)     |
| `/api/consulta`| POST   | Consulta completa (alternativo) |
| `/api/health`  | GET    | Estado del servicio          |
