# Defensor Laboral IA

Asistente RAG que responde consultas usando el **Código de Trabajo** y el **Contrato Colectivo de SITRACABAÑA** (Ingenio La Cabaña).

## Cómo funciona (RAG)

1. **Recuperación**: Busca los fragmentos más relevantes en `meta.jsonl`
2. **Síntesis (opcional)**: Si hay API key configurada, un LLM genera una respuesta natural citando artículos y cláusulas
3. **Fallback**: Sin LLM, muestra los fragmentos encontrados con sus referencias

## Configurar LLM (respuestas más naturales)

1. Obtén una API key gratuita en **Groq**: https://console.groq.com → API Keys
2. Edita `config/app.php` y pon tu key:
   ```php
   'defensor_llm_api_key' => 'gsk_tu_clave_aqui',
   ```
3. Sin API key el asistente sigue funcionando (solo recuperación)

## Archivos

- `app/services/DefensorService.php` – RAG (búsqueda + LLM)
- `app/services/LlmClient.php` – Cliente Groq/OpenAI
- `config/app.php` – Configuración LLM
- `public/js/chat-widget.js` – Chat en la landing

## Fuentes de datos

- `rag_sitracabana/data/index/meta.jsonl` – fragmentos del Contrato Colectivo y Código de Trabajo

## Uso

1. Abre la landing: `http://localhost/sitra_web/`
2. Haz clic en el botón verde del chat
3. Escribe tu consulta (despido, vacaciones, salario, etc.)
