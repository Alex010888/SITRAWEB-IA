<?php
/**
 * Definición de rutas.
 *
 * Formato: $router->get('/ruta', 'Controlador@metodo');
 */

/** @var \App\Core\Router $router */

$router->get('/', 'HomeController@index');

$router->get('/noticias', 'NewsController@index');
$router->get('/galeria', 'GalleryController@index');
$router->post('/galeria/subir', 'GalleryController@upload'); // preparado para panel/admin

$router->post('/afiliacion', 'AffiliateController@store');

// Defensor Laboral IA - API RAG (Código de Trabajo y Contrato Colectivo SITRACABAÑA)
$router->post('/api/defensor/consulta', 'DefensorController@consulta');
$router->get('/api/defensor/health', 'DefensorController@health');

