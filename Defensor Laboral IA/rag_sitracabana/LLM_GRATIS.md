# Alternativas gratuitas a OpenAI para el Defensor Laboral IA

El asistente puede usar varios proveedores de LLM. Estas opciones son **gratis** o tienen plan gratuito generoso.

---

## 1. Ollama (100 % gratis, local)

**Sin API key.** Los modelos se ejecutan en tu PC. Ideal para desarrollo y uso interno.

### Pasos

1. **Instalar Ollama:** https://ollama.com (Windows / Mac / Linux).
2. **Descargar un modelo** (en terminal):
   ```bash
   ollama pull llama3.2
   ```
   Otros modelos: `llama3.1`, `mistral`, `phi3`, `gemma2`.
3. **Configurar el Defensor** (variables de entorno o en `config/app.php` si se usa):
   - `DEFENSOR_LLM_PROVIDER=ollama`
   - `DEFENSOR_LLM_MODEL=llama3.2`
   - No hace falta `DEFENSOR_LLM_API_KEY`.
4. Asegurarte de que **Ollama esté en marcha** (suele iniciarse solo; por defecto en `http://localhost:11434`).

### Opcional

- Si Ollama corre en otra máquina/puerto: `OLLAMA_BASE_URL=http://otro-pc:11434`.

---

## 2. Groq (gratis en la nube)

**Plan gratuito amplio.** Respuestas muy rápidas con modelos como Llama.

### Pasos

1. **Crear cuenta y API key:** https://console.groq.com (registro gratis).
2. **Obtener API key** en la consola de Groq.
3. **Configurar el Defensor:**
   - `DEFENSOR_LLM_PROVIDER=groq`
   - `DEFENSOR_LLM_MODEL=llama-3.1-8b-instant` (u otro modelo de Groq)
   - `DEFENSOR_LLM_API_KEY=gsk_tu_api_key_de_groq`

En **Render** o entorno con variables de entorno, usa `OPENAI_API_KEY` o la variable que leas para la API key y asígnale el valor de la API key de Groq cuando el provider sea `groq` (el código ya soporta Groq con la misma variable de “API key” que uses para el Defensor).

---

## Resumen

| Proveedor | Coste      | API key | Dónde corre |
|-----------|------------|--------|-------------|
| **Ollama** | Gratis     | No     | Tu PC       |
| **Groq**   | Plan gratis | Sí (groq.com) | Nube Groq |
| **OpenAI** | De pago    | Sí     | Nube OpenAI |

Para usar **solo gratis**, elige **Ollama** (local) o **Groq** (nube).
