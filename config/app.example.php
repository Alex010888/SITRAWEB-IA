<?php
/**
 * Configuración general de la app.
 * Copia este archivo como config/app.php y ajusta los valores.
 */

return [
    'name' => 'SITRACABAÑA',
    'timezone' => 'America/Guatemala',
    'base_url' => null,
    'backend_uploads_base' => null,
    'public_gallery_upload' => false,

    'defensor_llm_api_key' => 'TU_API_KEY_AQUI',  // Groq o OpenAI
    'defensor_llm_provider' => 'openai',           // 'groq' o 'openai'
    'defensor_llm_model' => 'gpt-4o-mini',
    'defensor_semantic_url' => 'http://127.0.0.1:5000/api/search',
    'defensor_langchain_url' => 'http://127.0.0.1:5000/api/consulta',
];
