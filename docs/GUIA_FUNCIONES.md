# Guía de Funciones - SITRACABAÑA Web

Referencia de funciones principales del proyecto.

---

## Índice

1. [DefensorService (RAG)](#1-defensorservice-rag)
2. [DefensorController](#2-defensorcontroller)
3. [LlmClient](#3-llmclient)
4. [Controladores del Sitio Público](#4-controladores-del-sitio-público)
5. [Modelos](#5-modelos)
6. [Core y Helpers](#6-core-y-helpers)
7. [Backend API](#7-backend-api)

---

## 1. DefensorService (RAG)

**Archivo:** `app/services/DefensorService.php`  
**Namespace:** `App\Services`

### Métodos Públicos

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `loadChunks()` | - | `array` | Carga todos los chunks desde `meta.jsonl` |
| `search($query)` | `string $query` | `array` | Búsqueda de chunks (semántica o por palabras) |
| `consulta($pregunta, $historial)` | `string $pregunta`, `array $historial` | `array` | Procesa la consulta y devuelve `{ success, respuesta, fuentes }` |
| `formatResponse($results, $pregunta)` | `array $results`, `string $pregunta` | `string` | Formatea respuesta con citas de artículos/cláusulas |

### Métodos Privados - Búsqueda

| Función | Descripción |
|---------|-------------|
| `tokenize($text)` | Tokeniza texto para búsqueda (normaliza acentos) |
| `tokenMatches($queryTokens, $chunkToken)` | Verifica coincidencia de tokens |
| `expandQuery($query)` | Expande consulta con sinónimos y jerga laboral |
| `semanticSearch($query)` | Llama al microservicio de búsqueda semántica |
| `consultaViaLangChain($pregunta)` | Delega al microservicio LangChain |
| `isNoiseChunk($text)` | Detecta chunks irrelevantes (derogados, etc.) |

### Métodos Privados - Detección de Intención

| Función | Retorno | Descripción |
|---------|---------|-------------|
| `isDespidoQuery($pregunta)` | `bool` | Detecta consultas sobre despido, cese, finiquito |
| `isVacacionesQuery($pregunta)` | `bool` | Detecta consultas sobre vacaciones |
| `isBonoQuery($pregunta)` | `bool` | Detecta consultas sobre bono |
| `isAguinaldoQuery($pregunta)` | `bool` | Detecta consultas sobre aguinaldo |
| `isAguinaldoContratoQuery($pregunta)` | `bool` | Aguinaldo según Contrato Colectivo |
| `isSalarioQuery($pregunta)` | `bool` | Detecta consultas sobre salario |
| `isContratoColectivoQuery($pregunta)` | `bool` | Detecta consultas sobre Contrato Colectivo |
| `isGreetingOrCasual($pregunta)` | `bool` | Detecta saludos o mensajes casuales |
| `isAguinaldoCalcRequest($pregunta)` | `bool` | Detecta petición de cálculo de aguinaldo |
| `lastBotAskedAguinaldoData($historial)` | `bool` | Verifica si el bot pidió datos de aguinaldo |

### Métodos Privados - Extracción y Lookup

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `parseClauseNumber($pregunta)` | `string $pregunta` | `?int` | Extrae número de cláusula ("cláusula 7", "primera cláusula") |
| `parseArticleNumber($pregunta)` | `string $pregunta` | `?int` | Extrae número de artículo ("artículo 55", "art 198") |
| `getClauseByNumber($num)` | `int $num` | `array` | Obtiene chunks de una cláusula del Contrato Colectivo |
| `getArticleByNumber($num)` | `int $num` | `array` | Obtiene chunks de un artículo del Código de Trabajo |
| `parseAguinaldoData($pregunta)` | `string $pregunta` | `?array` | Extrae salario, meses, sitracabana del mensaje |

### Métodos Privados - Chunks Específicos

| Función | Descripción |
|---------|-------------|
| `getDespidoChunks($chunks)` | Filtra chunks relevantes para despido (Cláusula 7, Arts. 55, 58, 59) |
| `getAguinaldoChunks($chunks)` | Filtra chunks de aguinaldo (Cláusula 56, Arts. 196-200) |
| `sortContratoFirst($results)` | Ordena resultados: Contrato Colectivo antes que Código de Trabajo |

### Métodos Privados - Respuesta y LLM

| Función | Descripción |
|---------|-------------|
| `getGreetingResponse($pregunta)` | Genera respuesta para saludos |
| `getAguinaldoQuestionsResponse()` | Genera preguntas para calcular aguinaldo |
| `calculateAguinaldo($salario, $meses, $sitracabana)` | Calcula monto de aguinaldo |
| `buildContextForLlm($results)` | Construye contexto con citas para el LLM |
| `synthesizeWithLlm($pregunta, $results)` | Genera respuesta con LLM (OpenAI/Groq) |
| `responseHasValidCitation($respuesta, $results)` | Verifica que la respuesta cite fuentes del contexto |
| `formatResponse($results, $pregunta)` | Formatea respuesta directa sin LLM |
| `getDocName($source)` | Nombre legible del documento (Código de Trabajo, Contrato Colectivo) |
| `formatRefLabel($ref, $docName)` | Formatea etiqueta de referencia |
| `trimText($text, $maxChars)` | Recorta texto a máximo de caracteres |
| `hasContratoColectivoChunks($results)` | Verifica si hay chunks del Contrato Colectivo |

---

## 2. DefensorController

**Archivo:** `app/controllers/DefensorController.php`  
**Namespace:** `App\Controllers`

| Función | Método | Descripción |
|---------|--------|-------------|
| `consulta()` | POST | Recibe `pregunta` y `historial`, devuelve JSON `{ success, respuesta, fuentes }` |
| `health()` | GET | Verifica que `meta.jsonl` exista, devuelve estado del Defensor |

---

## 3. LlmClient

**Archivo:** `app/services/LlmClient.php`  
**Namespace:** `App\Services`

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `__construct($apiKey, $provider, $model)` | Configuración | - | Inicializa cliente LLM |
| `chat($messages, $maxTokens, $temperature)` | `array $messages`, `int $maxTokens`, `float $temperature` | `?string` | Envía mensajes al LLM y devuelve respuesta |

---

## 4. Controladores del Sitio Público

### HomeController

| Función | Descripción |
|---------|-------------|
| `index()` | Renderiza página principal con noticias, galería, directiva, documentos |
| `notFound()` | Renderiza vista 404 |

### NewsController

| Función | Descripción |
|---------|-------------|
| `index()` | Lista noticias publicadas |

### GalleryController

| Función | Descripción |
|---------|-------------|
| `index()` | Muestra galería de fotos |
| `upload()` | Sube imagen (si `public_gallery_upload` está activo) |

### AffiliateController

| Función | Descripción |
|---------|-------------|
| `store()` | Procesa formulario de afiliación, guarda en BD |

---

## 5. Modelos

### News

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `latest($limit)` | `int $limit` | `array` | Últimas N noticias publicadas |
| `allPublished()` | - | `array` | Todas las noticias publicadas |

### Gallery

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `latest($limit)` | `int $limit` | `array` | Últimas N fotos |
| `all()` | - | `array` | Todas las fotos |
| `create($title, $imagePath)` | `string $title`, `string $imagePath` | `int` | Inserta nueva foto |

### Board

| Función | Retorno | Descripción |
|---------|---------|-------------|
| `all()` | `array` | Lista miembros de la directiva |

### Document

| Función | Retorno | Descripción |
|---------|---------|-------------|
| `all()` | `array` | Lista documentos descargables |

### Affiliate

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `create($name, $phone, $email, $message, $ip, $userAgent)` | 6 strings | `int` | Inserta solicitud de afiliación |

---

## 6. Core y Helpers

### Router

| Función | Parámetros | Descripción |
|---------|------------|-------------|
| `get($path, $handler)` | `string $path`, `string $handler` | Registra ruta GET |
| `post($path, $handler)` | `string $path`, `string $handler` | Registra ruta POST |
| `dispatch($method, $path)` | `string $method`, `string $path` | Despacha la petición |

### Controller (base)

| Función | Parámetros | Descripción |
|---------|------------|-------------|
| `render($view, $data, $status)` | `string $view`, `array $data`, `int $status` | Renderiza vista con layout |
| `redirect($to)` | `string $to` | Redirige a URL |
| `json($payload, $status)` | `array $payload`, `int $status` | Devuelve JSON |

### Request

| Función | Retorno | Descripción |
|---------|---------|-------------|
| `method()` | `string` | Método HTTP (GET, POST, etc.) |
| `path()` | `string` | Ruta de la petición |
| `input($key, $default)` | `mixed` | Valor de POST/GET |
| `ip()` | `string` | IP del cliente |
| `userAgent()` | `string` | User-Agent |

### Session

| Función | Parámetros | Descripción |
|---------|------------|-------------|
| `start()` | - | Inicia sesión |
| `set($key, $value)` | `string $key`, `mixed $value` | Guarda valor |
| `get($key, $default)` | `string $key`, `mixed $default` | Obtiene valor |
| `forget($key)` | `string $key` | Elimina valor |
| `flash($key, $value)` | `string $key`, `?string $value` | Flash message |

### Helpers (app/core/helpers.php)

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `e($value)` | `?string $value` | `string` | Escapa HTML |
| `base_url()` | - | `string` | URL base del sitio |
| `url($path)` | `string $path` | `string` | URL absoluta |
| `asset($path)` | `string $path` | `string` | URL de asset |
| `asset_v($path)` | `string $path` | `string` | Asset con versionado (filemtime) |
| `get_cms_section($key)` | `string $key` | `?array` | Lee sección del CMS |
| `site_logo_path()` | - | `string` | Path/URL del logo |
| `site_logo_url()` | - | `string` | URL del logo para `<img>` |
| `uploaded_asset_url($pathOrUrl)` | `string $pathOrUrl` | `string` | URL de archivo subido |
| `csrf_token()` | - | `string` | Token CSRF |
| `csrf_field()` | - | `string` | Input hidden CSRF |
| `csrf_verify()` | - | `bool` | Verifica token CSRF |

### Database

| Función | Retorno | Descripción |
|---------|---------|-------------|
| `Database::pdo()` | `PDO` | Conexión PDO singleton |

### Upload

| Función | Parámetros | Retorno | Descripción |
|---------|------------|---------|-------------|
| `Upload::image($file, $targetDirAbs, $targetUrlPrefix)` | `array $file`, `string $targetDirAbs`, `string $targetUrlPrefix` | `array` | Sube imagen, devuelve path y URL |

---

## 7. Backend API

### AuthController

| Función | Método | Descripción |
|---------|--------|-------------|
| `login()` | POST | Valida credenciales, devuelve JWT |
| `logout()` | POST | Invalida token |
| `me()` | GET | Devuelve usuario actual |

### SectionController

| Función | Método | Descripción |
|---------|--------|-------------|
| `index()` | GET | Lista secciones (admin) |
| `show($key)` | GET | Obtiene sección |
| `update($key)` | PUT | Actualiza sección |
| `delete($key)` | DELETE | Elimina sección |
| `publicIndex()` | GET | Lista secciones (público) |
| `publicShow($key)` | GET | Obtiene sección (público) |

### MediaController

| Función | Método | Descripción |
|---------|--------|-------------|
| `upload()` | POST | Sube archivo (imagen/PDF) |
| `index()` | GET | Lista media con filtros |
| `delete($id)` | DELETE | Elimina media |
| `publicIndex()` | GET | Lista media pública |

### UserController

| Función | Método | Descripción |
|---------|--------|-------------|
| `index()` | GET | Lista usuarios |
| `show($id)` | GET | Usuario por ID |
| `store()` | POST | Crear usuario |
| `update($id)` | PUT | Actualizar usuario |
| `delete($id)` | DELETE | Eliminar usuario |

### ActivityLogController

| Función | Método | Descripción |
|---------|--------|-------------|
| `index()` | GET | Lista logs con paginación y filtros |

### Servicios Backend

| Servicio | Funciones Principales |
|----------|----------------------|
| **JwtService** | `encode($payload)`, `decode($token)` |
| **SectionService** | `get($key)`, `update($key, $content)` |
| **MediaService** | `upload($file, $sectionKey)`, `delete($id)` |
| **ActivityLogService** | `log($userId, $action, $entity, $entityId, $description)` |
| **PermissionService** | `getUserPermissions($userId)`, `hasPermission($userId, $perm)` |

---

*Guía de funciones - SITRACABAÑA Web*
