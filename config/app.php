<?php
/**
 * Configuración general de la app.
 * Copia este archivo como config/app.php y ajusta los valores.
 */

if (!function_exists('_defensor_url')) {
    function _defensor_url(string $urlKey, string $path): string
    {
        $full = getenv($urlKey);
        if (is_string($full) && $full !== '') {
            return $full;
        }
        $host = getenv('DEFENSOR_HOST');
        $port = getenv('DEFENSOR_PORT');
        if (is_string($host) && $host !== '' && is_string($port) && $port !== '') {
            return 'http://' . $host . ':' . $port . $path;
        }
        return 'http://127.0.0.1:5000' . $path;
    }
}

return [
    'name' => 'SITRACABAÑA',
    'timezone' => 'America/Guatemala',
    'base_url' => null,
    'backend_uploads_base' => null,
    'public_gallery_upload' => false,

    // API key desde variable de entorno (nunca commitear la clave en el repo)
    'defensor_llm_api_key' => getenv('DEFENSOR_LLM_API_KEY') ?: '',
    'defensor_llm_provider' => getenv('DEFENSOR_LLM_PROVIDER') ?: 'openai',
    'defensor_llm_model' => getenv('DEFENSOR_LLM_MODEL') ?: 'gpt-4o-mini',
    // URLs del Defensor: DEFENSOR_SEMANTIC_URL / DEFENSOR_LANGCHAIN_URL (completas) o DEFENSOR_HOST + DEFENSOR_PORT (Render)
    'defensor_semantic_url' => _defensor_url('DEFENSOR_SEMANTIC_URL', '/api/search'),
    'defensor_langchain_url' => _defensor_url('DEFENSOR_LANGCHAIN_URL', '/api/consulta'),
];
