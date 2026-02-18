# Defensor Laboral IA - LangChain + FAISS

Refactor del asistente usando **LangChain** para el pipeline RAG y **FAISS** como vector store.

## Arquitectura

```
Consulta → [Saludos?] → Sí → Respuesta directa
                    → No → FAISS Retriever → LangChain Chain → LLM → Respuesta
```

- **FAISS**: Índice de embeddings (SentenceTransformer all-MiniLM-L6-v2)
- **Retriever**: Custom `FAISSRetriever` que envuelve FAISS + SentenceTransformer
- **Chain**: `retriever | format_docs | prompt | llm | StrOutputParser`
- **LLM**: OpenAI (gpt-4o-mini) o Groq (llama-3.1-8b-instant)

## Requisitos

- Python 3.9–3.12 (LangChain puede no soportar 3.13 aún)
- Índice FAISS generado (`scripts/04_build_faiss_index.py`)

```bash
pip install langchain langchain-core langchain-community langchain-openai langchain-groq
```

O desde `requirements.txt`:

```bash
pip install -r requirements.txt
```

## Iniciar

```bash
START_ASISTENTE_LANGCHAIN.bat
```

O manualmente:

```bash
cd "Defensor Laboral IA/rag_sitracabana"
..\venv\Scripts\activate
python app_langchain.py
```

Servidor: **http://127.0.0.1:5000**

## Configuración

La API key y modelo se leen de `config/app.php`:

- `defensor_llm_api_key`
- `defensor_llm_provider` (openai | groq)
- `defensor_llm_model`

O variables de entorno: `OPENAI_API_KEY`, `DEFENSOR_LLM_PROVIDER`, `DEFENSOR_LLM_MODEL`

## Integración con PHP

En `config/app.php`:

```php
'defensor_langchain_url' => 'http://127.0.0.1:5000/api/consulta',
```

Si está configurado, PHP delega toda la consulta al microservicio LangChain. Si falla o es `null`, usa el flujo PHP (búsqueda + LLM en PHP).

## Endpoints

| Endpoint      | Método | Descripción                    |
|---------------|--------|--------------------------------|
| `/api/consulta` | POST   | Consulta RAG completa         |
| `/api/health`   | GET    | Estado del servicio           |
