<?php
/**
 * Configuración general de la app.
 */

return [
    'name' => 'SITRACABAÑA',
    'timezone' => 'America/Guatemala',

    /**
     * Base URL (opcional).
     * - Dejar null para autodetección.
     * - Ejemplo si está en subcarpeta: '/sitra_web'
     */
    'base_url' => null,

    /**
     * URL base de uploads del backend (para fotos de directiva, logo subido, etc.).
     * Sin /uploads al final. Ej: http://localhost/sitra_web/backend
     */
    'backend_uploads_base' => null,

    /**
     * Seguridad: mantener en false hasta tener panel/admin con login.
     * Si lo activas en true, se habilita el endpoint POST /galeria/subir.
     */
    'public_gallery_upload' => false,

    /**
     * Defensor Laboral IA - LLM para síntesis RAG.
     * Poner tu API key para respuestas más naturales. Sin key = solo recuperación.
     * Groq (gratis): https://console.groq.com → API Keys
     * OpenAI: https://platform.openai.com
     */
    'defensor_llm_api_key' => '',

    /** Proveedor: 'groq' (gratis) o 'openai' */
    'defensor_llm_provider' => 'openai',

    /** Modelo. Groq: llama-3.1-8b-instant. OpenAI: gpt-4o-mini, gpt-4o */
    'defensor_llm_model' => 'gpt-4o-mini',

    /**
     * Búsqueda semántica (embeddings).
     * Si está configurado, PHP llama al microservicio Python para búsqueda por similitud.
     * Ejemplo: 'http://127.0.0.1:5000/api/search'
     * Dejar null para usar solo búsqueda por palabras. Si falla, hace fallback automático.
     */
    'defensor_semantic_url' => 'http://127.0.0.1:5000/api/search',  // null = solo búsqueda por palabras

    /**
     * API LangChain (RAG completo: FAISS + chain + LLM).
     * Si está configurado, PHP delega toda la consulta al microservicio LangChain.
     * Dejar null para usar el flujo PHP (búsqueda + LLM en PHP).
     */
    'defensor_langchain_url' => 'http://127.0.0.1:5000/api/consulta',
];

