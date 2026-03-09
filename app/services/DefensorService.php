<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Defensor Laboral IA - Servicio RAG
 * Búsqueda sobre Código de Trabajo y Contrato Colectivo SITRACABAÑA.
 * Usa búsqueda semántica (embeddings) cuando está disponible, con fallback a palabras clave.
 */
final class DefensorService
{
    private const TOP_K = 8;
    private const MIN_SCORE = 0.15;
    private const DESPIDO_ARTICLES = ['55', '57', '58', '59', '53', '60', '420'];
    private const DESPIDO_CLAUSULA = '7';
    private const AGUINALDO_CLAUSULA = '56';
    private const AGUINALDO_ARTICLES = ['196', '197', '198', '199', '200'];
    private const VACACIONES_CLAUSULA = '55';
    private const BONO_CLAUSULAS = ['55', '56', '52'];

    private ?array $chunks = null;
    private string $metaPath;

    public function __construct()
    {
        $this->metaPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . '/Defensor Laboral IA/rag_sitracabana/data/index/meta.jsonl';
    }

    /**
     * Carga los chunks desde meta.jsonl.
     * @return array<int, array{id: string, text: string, metadata: array}>
     */
    public function loadChunks(): array
    {
        if ($this->chunks !== null) {
            return $this->chunks;
        }

        if (!is_file($this->metaPath)) {
            throw new \RuntimeException('No se encontró meta.jsonl. Verifica la ruta: ' . $this->metaPath);
        }

        $lines = file($this->metaPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->chunks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $rec = json_decode($line, true);
            if (!is_array($rec)) {
                continue;
            }
            $this->chunks[] = [
                'id' => $rec['id'] ?? '',
                'text' => $rec['text'] ?? '',
                'metadata' => $rec['metadata'] ?? [],
            ];
        }

        return $this->chunks;
    }

    /**
     * Tokeniza texto para búsqueda (español, normaliza acentos).
     */
    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($tokens, fn($t) => mb_strlen($t) > 1));
    }

    /**
     * Verifica si un token del chunk coincide con uno del query.
     */
    private function tokenMatches(array $queryTokens, string $chunkToken): bool
    {
        foreach ($queryTokens as $qt) {
            if ($chunkToken === $qt || str_starts_with($chunkToken, $qt) || str_starts_with($qt, $chunkToken)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta si la consulta es sobre despido, cese, finiquito o preaviso.
     */
    private function isDespidoQuery(string $pregunta): bool
    {
        $q = mb_strtolower($pregunta);
        return (bool) (
            preg_match('/\b(despidieron|despedido|despidos?|me despidieron|me corrieron|me echaron|terminaron|me terminaron)\b/u', $q)
            || preg_match('/\b(despido|despidos)\s+injustificado[s]?|injustificado[s]?\s+(despido|despidos)\b/ui', $q)
            || preg_match('/\bindemnizaci[oó]n\s+(por\s+)?despido\b/u', $q)
            || preg_match('/\bsin\s+causa\s+justificada\b/ui', $q)
            || preg_match('/\b(me\s+)?liquidaron|liquidaci[oó]n|me\s+liquidaron\b/ui', $q)
            || preg_match('/\b(cese|cesaron|me\s+cesaron|cesant[ií]a)\b/ui', $q)
            || preg_match('/\b(preaviso|aviso\s+previo|aviso\s+de\s+despido)\b/ui', $q)
            || preg_match('/\b(finiquito|me\s+deben\s+el\s+finiquito|finiquitar)\b/ui', $q)
            || preg_match('/\b(me\s+sacaron|me\s+botaron|me\s+rajaron|retiro\s+forzado|renuncia\s+forzada)\b/ui', $q)
            || preg_match('/\b(me\s+despidieron|me\s+corrieron)\s+sin\s+(causa|motivo)\b/ui', $q)
        );
    }

    private function isVacacionesQuery(string $pregunta): bool
    {
        return (bool) preg_match('/\b(vacaciones|d[ií]as\s+de\s+vacaciones|descanso\s+anual|vacaciones\s+pagadas|no\s+me\s+dan\s+vacaciones|d[ií]as\s+de\s+descanso)\b/ui', $pregunta);
    }

    private function isBonoQuery(string $pregunta): bool
    {
        return (bool) preg_match('/\b(bono|bonificaci[oó]n|tarjeta\s+de\s+regalo|bono\s+navide[oñ]o|bono\s+de\s+diciembre)\b/ui', $pregunta);
    }

    private function isAguinaldoQuery(string $pregunta): bool
    {
        return (bool) preg_match('/\b(aguinaldo|aguinaldos|d[eé]cimo\s+tercero)\b/ui', $pregunta);
    }

    /**
     * Detecta si el usuario pide el aguinaldo según el Contrato Colectivo (Cláusula 56).
     */
    private function isAguinaldoContratoQuery(string $pregunta): bool
    {
        $q = mb_strtolower($pregunta);
        if (!str_contains($q, 'aguinaldo') && !str_contains($q, 'décimo') && !str_contains($q, 'decimo')) {
            return false;
        }
        return (bool) (
            preg_match('/\b(aguinaldo|d[eé]cimo)\s+(seg[uú]n|del?)\s+(el\s+)?contrato\s+colectivo\b/ui', $q)
            || preg_match('/\b(seg[uú]n|del?)\s+(el\s+)?contrato\s+colectivo.*(aguinaldo|d[eé]cimo)\b/ui', $q)
            || preg_match('/\b(aguinaldo|d[eé]cimo).*(contrato\s+colectivo)\b/ui', $q)
            || preg_match('/\b(contrato\s+colectivo).*(aguinaldo|d[eé]cimo)\b/ui', $q)
        );
    }

    private function isSalarioQuery(string $pregunta): bool
    {
        return (bool) preg_match('/\b(salario|sueldo|pago|me\s+deben|no\s+me\s+pagan|remuneraci[oó]n|quincena|planilla)\b/ui', $pregunta);
    }

    /** Ordinales en español para "primera cláusula", "décima cláusula", etc. */
    private const ORDINALES_CLAUSULA = [
        'primera' => 1, 'segunda' => 2, 'tercera' => 3, 'cuarta' => 4, 'quinta' => 5,
        'sexta' => 6, 'séptima' => 7, 'septima' => 7, 'octava' => 8, 'novena' => 9,
        'décima' => 10, 'decima' => 10, 'undécima' => 11, 'duodécima' => 12,
        'decimotercera' => 13, 'decimocuarta' => 14, 'decimoquinta' => 15,
        'decimosexta' => 16, 'decimoséptima' => 17, 'decimoctava' => 18, 'decimonovena' => 19,
        'vigésima' => 20, 'vigesima' => 20, 'vigesimaprimera' => 21, 'vigesimasegunda' => 22,
        'trigésima' => 30, 'trigesima' => 30, 'cuadragésima' => 40, 'quincuagésima' => 50,
        'sexagésima' => 60,
    ];

    /** Ordinales para "primer artículo", "artículo quincuagésimo", etc. */
    private const ORDINALES_ARTICULO = [
        'primer' => 1, 'primero' => 1, 'segundo' => 2, 'tercer' => 3, 'tercero' => 3,
        'cuarto' => 4, 'quinto' => 5, 'sexto' => 6, 'séptimo' => 7, 'septimo' => 7,
        'octavo' => 8, 'noveno' => 9, 'décimo' => 10, 'decimo' => 10,
        'undécimo' => 11, 'duodécimo' => 12, 'decimotercero' => 13, 'decimocuarto' => 14,
        'decimoquinto' => 15, 'vigésimo' => 20, 'vigesimo' => 20, 'trigésimo' => 30,
        'cuadragésimo' => 40, 'quincuagésimo' => 50, 'sexagésimo' => 60,
    ];

    /**
     * Extrae el número de cláusula si el usuario pide una cláusula específica.
     * Soporta: "cláusula 7", "primera cláusula", "décima cláusula", "la segunda cláusula"
     */
    private function parseClauseNumber(string $pregunta): ?int
    {
        $q = mb_strtolower($pregunta);

        if (preg_match('/\bcl[aá]usula\s+(\d{1,4})\b/ui', $q, $m)) {
            $n = (int) $m[1];
            return $n >= 1 ? $n : null;
        }
        if (preg_match('/\b(?:la\s+)?cl[aá]usula\s+(\d{1,4})\s+(?:del?\s+)?contrato/ui', $q, $m)) {
            $n = (int) $m[1];
            return $n >= 1 ? $n : null;
        }

        if (preg_match('/\b(?:la\s+)?([a-záéíóúñ]+)\s+cl[aá]usula\b/ui', $q, $m)) {
            $ord = mb_strtolower(trim($m[1]));
            if (isset(self::ORDINALES_CLAUSULA[$ord])) {
                return self::ORDINALES_CLAUSULA[$ord];
            }
        }
        if (preg_match('/\bcl[aá]usula\s+([a-záéíóúñ]+)\b/ui', $q, $m)) {
            $ord = mb_strtolower(trim($m[1]));
            if (isset(self::ORDINALES_CLAUSULA[$ord])) {
                return self::ORDINALES_CLAUSULA[$ord];
            }
        }

        return null;
    }

    /**
     * Extrae el número de artículo si el usuario pide un artículo específico del Código de Trabajo.
     * Soporta: "artículo 55", "art 55", "art 55 del código de trabajo", "primer artículo"
     */
    private function parseArticleNumber(string $pregunta): ?int
    {
        $q = mb_strtolower($pregunta);

        if (preg_match('/\b(?:art[ií]culo|art\.?)\s+(\d{1,4})\b/ui', $q, $m)) {
            $n = (int) $m[1];
            return $n >= 1 ? $n : null;
        }
        if (preg_match('/\b(?:art[ií]culo|art\.?)\s+(\d{1,4})\s+(?:del?\s+)?c[oó]digo\s+(?:de\s+)?trabajo/ui', $q, $m)) {
            $n = (int) $m[1];
            return $n >= 1 ? $n : null;
        }
        if (preg_match('/\bc[oó]digo\s+(?:de\s+)?trabajo.*(?:art[ií]culo|art\.?)\s+(\d{1,4})\b/ui', $q, $m)) {
            $n = (int) $m[1];
            return $n >= 1 ? $n : null;
        }

        if (preg_match('/\b(?:el\s+)?([a-záéíóúñ]+)\s+art[ií]culo\b/ui', $q, $m)) {
            $ord = mb_strtolower(trim($m[1]));
            if (isset(self::ORDINALES_ARTICULO[$ord])) {
                return self::ORDINALES_ARTICULO[$ord];
            }
        }
        if (preg_match('/\bart[ií]culo\s+([a-záéíóúñ]+)\b/ui', $q, $m)) {
            $ord = mb_strtolower(trim($m[1]));
            if (isset(self::ORDINALES_ARTICULO[$ord])) {
                return self::ORDINALES_ARTICULO[$ord];
            }
        }

        return null;
    }

    /**
     * Obtiene los chunks de un artículo específico del Código de Trabajo.
     * @return array<int, array{id: string, text: string, metadata: array}>
     */
    private function getArticleByNumber(int $num): array
    {
        $chunks = $this->loadChunks();
        $refNum = (string) $num;
        $found = [];
        foreach ($chunks as $chunk) {
            $md = $chunk['metadata'] ?? [];
            if (($md['source'] ?? '') !== 'codigo_trabajo') {
                continue;
            }
            if ((string)($md['ref_num'] ?? '') !== $refNum) {
                continue;
            }
            $part = (int)($md['part'] ?? 1);
            $found[$part] = $chunk;
        }
        ksort($found);
        return array_values($found);
    }

    /**
     * Obtiene los chunks de una cláusula específica del Contrato Colectivo.
     * @return array<int, array{id: string, text: string, metadata: array}>
     */
    private function getClauseByNumber(int $num): array
    {
        $chunks = $this->loadChunks();
        $refNum = (string) $num;
        $found = [];
        foreach ($chunks as $chunk) {
            $md = $chunk['metadata'] ?? [];
            if (($md['source'] ?? '') !== 'cct_sitracabana') {
                continue;
            }
            if ((string)($md['ref_num'] ?? '') !== $refNum) {
                continue;
            }
            $part = (int)($md['part'] ?? 1);
            $found[$part] = $chunk;
        }
        ksort($found);
        return array_values($found);
    }

    /**
     * Detecta si la consulta pide explícitamente qué dice el Contrato Colectivo o sus cláusulas.
     */
    private function isContratoColectivoQuery(string $pregunta): bool
    {
        $q = mb_strtolower($pregunta);
        return (bool) preg_match('/\b(contrato\s+colectivo|qu[eé]\s+dice\s+el\s+contrato|cl[aá]usulas?\s+(del?\s+)?contrato|qu[eé]\s+establece\s+el\s+contrato|busca\s+(en\s+)?(el\s+)?contrato)\b/ui', $q);
    }

    /**
     * Expande la consulta con sinónimos y jerga laboral para mejorar la búsqueda.
     */
    private function expandQuery(string $query): string
    {
        $q = mb_strtolower(trim($query));
        $expansions = [];

        // Despido, cese, finiquito, preaviso
        if ($this->isDespidoQuery($query)) {
            $expansions[] = 'despido indemnización terminación contrato sin causa justificada derechos trabajador patrono '
                . 'liquidación finiquito preaviso cese cesantía retiro artículo 55 58 59';
        }
        // Vacaciones
        if ($this->isVacacionesQuery($query)) {
            $expansions[] = 'vacaciones días remuneradas prestación descanso anual vacaciones pagadas cláusula';
        }
        // Salario
        if ($this->isSalarioQuery($query)) {
            $expansions[] = 'salario sueldo pago remuneración patrono obligación devengado quincena planilla';
        }
        // Aguinaldo
        if ($this->isAguinaldoQuery($query)) {
            $expansions[] = 'aguinaldo compensación diciembre prestación décimo tercero beneficio';
        }
        // Bono / bonificación
        if ($this->isBonoQuery($query)) {
            $expansions[] = 'bono bonificación tarjeta regalo prestación beneficio diciembre compensación cláusula';
        }
        // Contrato colectivo
        if ($this->isContratoColectivoQuery($query)) {
            $expansions[] = 'cláusula contrato colectivo SITRACABAÑA convenio';
        }
        // Horas extras (nuevo)
        if (preg_match('/\b(horas?\s+extras?|sobretiempo|tiempo\s+extra)\b/ui', $q)) {
            $expansions[] = 'horas extras sobretiempo recargo nocturno dominical remuneración';
        }
        // Días de descanso / dominical
        if (preg_match('/\b(d[ií]a\s+de\s+descanso|dominical|descanso\s+semanal)\b/ui', $q)) {
            $expansions[] = 'descanso dominical semanal remunerado prestación';
        }
        // Permiso / licencia
        if (preg_match('/\b(permiso|licencia|d[ií]as\s+libres|ausencia)\b/ui', $q)) {
            $expansions[] = 'permiso licencia ausencia justificada prestación';
        }
        // Prestaciones (genérico)
        if (preg_match('/\b(prestaciones|beneficios\s+laborales)\b/ui', $q)) {
            $expansions[] = 'vacaciones aguinaldo bono bonificación prestación beneficio';
        }

        return $expansions !== [] ? $query . ' ' . implode(' ', $expansions) : $query;
    }

    /**
     * Detecta si un chunk es "ruido" (disposiciones/amendments sin contenido sustantivo).
     */
    private function isNoiseChunk(string $text): bool
    {
        $start = mb_substr($text, 0, 400);
        return (bool) preg_match('/^(D\.\s*L\.\s*No\.|D\.\s*O\.\s*No\.|DISPOSICI[OÓ]N[ES]*\s+(TRANSITORIA|RELACIONADA)|No\.\s*\d+,\s*\d+\s+DE\s|I[OÓ]N\s+TRANSITORIA|ORDINAL\s+\d|ART[IÍ]CULO\s+\d+\s+ORDINAL)/ui', $start);
    }

    /**
     * Delega la consulta al microservicio LangChain (FAISS + RAG chain + LLM).
     * @return array{success: bool, respuesta: string, fuentes: array}|null null si falla o no está configurado
     */
    private function consultaViaLangChain(string $pregunta): ?array
    {
        $config = require (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/config/app.php';
        $url = $config['defensor_langchain_url'] ?? null;
        if (!is_string($url) || $url === '') {
            return null;
        }

        $payload = json_encode(['pregunta' => $pregunta]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 30.0,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return null;
        }

        return [
            'success' => true,
            'respuesta' => $data['respuesta'] ?? '',
            'fuentes' => $data['fuentes'] ?? [],
        ];
    }

    /**
     * Llama al microservicio de búsqueda semántica (Python + FAISS).
     * @return array<int, array{id: string, text: string, metadata: array, score: float}>|null null si falla
     */
    private function semanticSearch(string $query): ?array
    {
        $config = require (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/config/app.php';
        $url = $config['defensor_semantic_url'] ?? null;
        if (!is_string($url) || $url === '') {
            return null;
        }

        $payload = json_encode([
            'query' => $query,
            'top_k' => self::TOP_K,
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 5.0,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return null;
        }

        $chunks = $data['chunks'] ?? [];
        if (!is_array($chunks) || $chunks === []) {
            return null;
        }

        $result = [];
        foreach ($chunks as $c) {
            if (!is_array($c) || empty($c['text'])) {
                continue;
            }
            if ($this->isNoiseChunk($c['text'] ?? '')) {
                continue;
            }
            $result[] = [
                'id' => $c['id'] ?? '',
                'text' => $c['text'] ?? '',
                'metadata' => $c['metadata'] ?? [],
                'score' => (float)($c['score'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Para consultas de despido: obtiene chunks con contenido sustantivo sobre despido/indemnización.
     */
    private function getDespidoChunks(array $chunks): array
    {
        // Contrato Colectivo (Cláusula 7) primero, luego Código de Trabajo
        $order = ['7' => 1, '55' => 2, '58' => 3, '59' => 4, '57' => 5, '420' => 6, '53' => 7, '60' => 8];
        $found = [];
        foreach ($chunks as $chunk) {
            $md = $chunk['metadata'] ?? [];
            $source = $md['source'] ?? '';
            $refNum = (string)($md['ref_num'] ?? '');
            $text = $chunk['text'] ?? '';
            if ($this->isNoiseChunk($text)) {
                continue;
            }
            $isRelevant = ($source === 'codigo_trabajo' && in_array($refNum, self::DESPIDO_ARTICLES, true))
                || ($source === 'cct_sitracabana' && $refNum === self::DESPIDO_CLAUSULA);
            if (!$isRelevant) {
                continue;
            }
            $key = $source . '|' . $refNum;
            if (!isset($found[$key]) || mb_strlen($text) > mb_strlen($found[$key]['text'] ?? '')) {
                $found[$key] = $chunk + ['score' => 1.0 - ($order[$refNum] ?? 99) * 0.01];
            }
        }
        usort($found, function ($a, $b) use ($order) {
            $refA = (string)($a['metadata']['ref_num'] ?? '');
            $refB = (string)($b['metadata']['ref_num'] ?? '');
            $ordA = $order[$refA] ?? 99;
            $ordB = $order[$refB] ?? 99;
            if ($ordA !== $ordB) {
                return $ordA <=> $ordB;
            }
            $srcA = $a['metadata']['source'] ?? '';
            $srcB = $b['metadata']['source'] ?? '';
            return ($srcA === 'cct_sitracabana' ? 0 : 1) <=> ($srcB === 'cct_sitracabana' ? 0 : 1);
        });
        return array_values($found);
    }

    /**
     * Para consultas de aguinaldo: Contrato Colectivo (Cláusula 56) primero, luego Código de Trabajo.
     */
    private function getAguinaldoChunks(array $chunks): array
    {
        $order = ['56' => 1, '196' => 2, '197' => 3, '198' => 4, '199' => 5, '200' => 6];
        $found = [];
        foreach ($chunks as $chunk) {
            $md = $chunk['metadata'] ?? [];
            $source = $md['source'] ?? '';
            $refNum = (string)($md['ref_num'] ?? '');
            $text = $chunk['text'] ?? '';
            if ($this->isNoiseChunk($text)) {
                continue;
            }
            $isRelevant = ($source === 'codigo_trabajo' && in_array($refNum, self::AGUINALDO_ARTICLES, true))
                || ($source === 'cct_sitracabana' && $refNum === self::AGUINALDO_CLAUSULA);
            if (!$isRelevant) {
                continue;
            }
            $key = $source . '|' . $refNum;
            if (!isset($found[$key]) || mb_strlen($text) > mb_strlen($found[$key]['text'] ?? '')) {
                $found[$key] = $chunk + ['score' => 1.0 - ($order[$refNum] ?? 99) * 0.01];
            }
        }
        usort($found, function ($a, $b) use ($order) {
            $refA = (string)($a['metadata']['ref_num'] ?? '');
            $refB = (string)($b['metadata']['ref_num'] ?? '');
            $ordA = $order[$refA] ?? 99;
            $ordB = $order[$refB] ?? 99;
            if ($ordA !== $ordB) {
                return $ordA <=> $ordB;
            }
            $srcA = $a['metadata']['source'] ?? '';
            $srcB = $b['metadata']['source'] ?? '';
            return ($srcA === 'cct_sitracabana' ? 0 : 1) <=> ($srcB === 'cct_sitracabana' ? 0 : 1);
        });
        return array_values($found);
    }

    /**
     * Ordena resultados para priorizar Contrato Colectivo (cct_sitracabana) primero.
     */
    private function sortContratoFirst(array $results): array
    {
        usort($results, function ($a, $b) {
            $srcA = $a['metadata']['source'] ?? '';
            $srcB = $b['metadata']['source'] ?? '';
            $prioA = $srcA === 'cct_sitracabana' ? 0 : 1;
            $prioB = $srcB === 'cct_sitracabana' ? 0 : 1;
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });
        return $results;
    }

    /**
     * Busca los chunks más relevantes.
     * Usa búsqueda semántica (embeddings) si está disponible, sino búsqueda por palabras.
     * @return array<int, array{id: string, text: string, metadata: array, score: float}>
     */
    public function search(string $query): array
    {
        $chunks = $this->loadChunks();
        $isDespido = $this->isDespidoQuery($query);

        // 1) Para despido: usar SOLO chunks específicos (evitar artículos derogados/irrelevantes de búsqueda semántica)
        if ($isDespido) {
            $despidoChunks = $this->getDespidoChunks($chunks);
            if ($despidoChunks !== []) {
                return $this->sortContratoFirst(array_slice($despidoChunks, 0, self::TOP_K));
            }
        }

        // 2) Para aguinaldo: Contrato Colectivo (Cláusula 56) primero, luego Código de Trabajo
        $isAguinaldo = $this->isAguinaldoQuery($query);
        if ($isAguinaldo) {
            $aguinaldoChunks = $this->getAguinaldoChunks($chunks);
            if ($aguinaldoChunks !== []) {
                return array_slice($aguinaldoChunks, 0, self::TOP_K);
            }
        }

        // 3) Intentar búsqueda semántica (embeddings) para otras consultas
        $semanticResults = $this->semanticSearch($query);
        if ($semanticResults !== null && $semanticResults !== []) {
            return $this->sortContratoFirst(array_slice($semanticResults, 0, self::TOP_K));
        }

        // 2) Fallback: búsqueda por palabras clave
        $despidoChunks = [];
        if ($isDespido) {
            $despidoChunks = $this->getDespidoChunks($chunks);
            if ($despidoChunks !== []) {
                $ids = array_column($despidoChunks, 'id');
                $rest = array_filter($chunks, fn($c) => !in_array($c['id'] ?? '', $ids, true));
                $chunks = $rest;
            }
        }

        $expandedQuery = $this->expandQuery($query);
        $queryTokens = $this->tokenize($expandedQuery);

        if ($queryTokens === [] && !$isDespido) {
            return [];
        }

        $queryTokenArr = array_unique($queryTokens);
        $scored = [];

        foreach ($chunks as $chunk) {
            if ($queryTokenArr === []) {
                continue;
            }
            $textTokens = $this->tokenize($chunk['text']);
            $textLower = mb_strtolower($chunk['text'], 'UTF-8');

            $score = 0.0;
            foreach ($queryTokenArr as $qt) {
                $matches = array_filter($textTokens, fn($t) => $this->tokenMatches([$qt], $t) || str_contains($t, $qt) || str_contains($qt, $t));
                if ($matches !== []) {
                    $score += count($matches);
                }
                if (str_contains($textLower, $qt)) {
                    $score += 2;
                }
            }

            $norm = count($queryTokenArr) * (1 + log(1 + count($textTokens)));
            $score = $norm > 0 ? $score / $norm : 0;

            $md = $chunk['metadata'];
            $refNum = (string)($md['ref_num'] ?? '');
            $source = $md['source'] ?? '';
            if ($this->isNoiseChunk($chunk['text'] ?? '')) {
                $score = 0;
            }
            if ($isDespido && $source === 'codigo_trabajo' && in_array($refNum, self::DESPIDO_ARTICLES, true)) {
                $score = max($score, 0.5);
            }
            if ($isDespido && $source === 'cct_sitracabana' && $refNum === self::DESPIDO_CLAUSULA) {
                $score = max($score, 0.4);
            }
            if ($this->isVacacionesQuery($query) && $source === 'cct_sitracabana' && $refNum === self::VACACIONES_CLAUSULA) {
                $score = max($score, 0.6);
            }
            if ($this->isBonoQuery($query) && $source === 'cct_sitracabana' && in_array($refNum, self::BONO_CLAUSULAS, true)) {
                $score = max($score, 0.5);
            }
            if ($this->isAguinaldoQuery($query) && $source === 'cct_sitracabana' && in_array($refNum, self::BONO_CLAUSULAS, true)) {
                $score = max($score, 0.5);
            }
            if ($this->isSalarioQuery($query) && $source === 'cct_sitracabana') {
                $score = max($score, 0.4);
            }
            if ($this->isContratoColectivoQuery($query) && $source === 'cct_sitracabana') {
                $score = max($score, 0.6);
            }

            $scored[] = $chunk + ['score' => $score];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        $maxScore = $scored[0]['score'] ?? 1.0;
        $threshold = $maxScore > 0 ? max(self::MIN_SCORE, $maxScore * 0.15) : self::MIN_SCORE;

        $filtered = array_filter($scored, fn($r) => $r['score'] >= $threshold);
        $normalResults = array_slice(array_values($filtered), 0, self::TOP_K);

        if ($isDespido && $despidoChunks !== []) {
            $merged = array_merge($despidoChunks, array_slice($normalResults, 0, max(0, self::TOP_K - count($despidoChunks))));
            return $this->sortContratoFirst($merged);
        }

        return $this->sortContratoFirst($normalResults);
    }

    private function getDocName(string $source): string
    {
        return match ($source) {
            'cct_sitracabana' => 'Contrato Colectivo SITRACABAÑA',
            'codigo_trabajo' => 'Código de Trabajo',
            default => $source ?: 'Documento',
        };
    }

    private function formatRefLabel(string $ref, string $docName): string
    {
        if ($ref === '') {
            return $docName;
        }
        if (str_starts_with($ref, 'ART')) {
            return 'Art. ' . preg_replace('/^ART\s*/i', '', $ref);
        }
        if (str_starts_with($ref, 'CLA')) {
            return 'Cláusula ' . preg_replace('/^CLA\s*/i', '', $ref);
        }
        return $ref;
    }

    /**
     * Recorta texto largo conservando oraciones completas.
     */
    private function trimText(string $text, int $maxChars = 450): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text));
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }
        $cut = mb_substr($text, 0, $maxChars);
        $last = mb_strrpos($cut, '.');
        return $last !== false ? mb_substr($cut, 0, $last + 1) : $cut . '…';
    }

    /**
     * Indica si hay chunks del Contrato Colectivo en los resultados.
     */
    private function hasContratoColectivoChunks(array $results): bool
    {
        foreach ($results as $r) {
            if (($r['metadata']['source'] ?? '') === 'cct_sitracabana') {
                return true;
            }
        }
        return false;
    }

    /**
     * Formatea respuesta directa: artículo/cláusula + texto esencial.
     */
    public function formatResponse(array $results, string $pregunta): string
    {
        if ($results === []) {
            $msg = 'No encontré información para tu consulta.';
            if ($this->isContratoColectivoQuery($pregunta)) {
                $msg = 'No encontré cláusulas en el Contrato Colectivo SITRACABAÑA sobre este tema.';
            }
            return $msg . ' Contacta al sindicato: contacto@sitra-lacabana.org';
        }

        $hasCC = $this->hasContratoColectivoChunks($results);
        if ($this->isContratoColectivoQuery($pregunta) && !$hasCC) {
            $ctParts = [];
            foreach ($results as $r) {
                $md = $r['metadata'] ?? [];
                if (($md['source'] ?? '') !== 'codigo_trabajo') {
                    continue;
                }
                $ref = trim(($md['ref_type'] ?? '') . ' ' . ($md['ref_num'] ?? ''));
                $refLabel = $this->formatRefLabel($ref, 'Código de Trabajo');
                $ctParts[] = '**' . $refLabel . '**: ' . $this->trimText($r['text'] ?? '');
            }
            $ctText = implode("\n\n", $ctParts);
            return 'No encontré cláusulas en el Contrato Colectivo SITRACABAÑA sobre este tema. '
                . 'La información que encontré proviene del Código de Trabajo:' . "\n\n" . $ctText
                . "\n\n*Para consultas específicas del Contrato Colectivo: contacto@sitra-lacabana.org*";
        }

        $parts = [];
        $seenKeys = [];
        $items = [];

        foreach ($results as $r) {
            $md = $r['metadata'] ?? [];
            $source = $md['source'] ?? '';
            $refType = $md['ref_type'] ?? '';
            $refNum = $md['ref_num'] ?? null;
            $ref = trim(($refType ? $refType . ' ' : '') . ($refNum !== null ? (string)$refNum : ''));
            $docName = $this->getDocName($source);
            $key = $docName . '|' . $ref;

            if (in_array($key, $seenKeys, true)) {
                continue;
            }
            $text = trim($r['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $seenKeys[] = $key;
            $items[] = ['doc' => $docName, 'ref' => $ref, 'text' => $text, 'score' => $r['score']];
        }

        usort($items, function ($a, $b) {
            $ccA = ($a['doc'] ?? '') === 'Contrato Colectivo SITRACABAÑA' ? 0 : 1;
            $ccB = ($b['doc'] ?? '') === 'Contrato Colectivo SITRACABAÑA' ? 0 : 1;
            if ($ccA !== $ccB) {
                return $ccA <=> $ccB;
            }
            return $b['score'] <=> $a['score'];
        });
        $items = array_slice($items, 0, 3);

        $fuentesList = [];
        foreach ($items as $item) {
            $refLabel = $this->formatRefLabel($item['ref'], $item['doc']);
            $parts[] = '**' . $refLabel . '** (' . $item['doc'] . '):';
            $parts[] = $this->trimText($item['text']);
            $parts[] = '';
            $abrev = ($item['doc'] ?? '') === 'Contrato Colectivo SITRACABAÑA' ? 'CC' : 'CT';
            $fuentesList[] = $refLabel . ' ' . $abrev;
        }
        if ($fuentesList !== []) {
            $parts[] = '*Fuentes: ' . implode(', ', $fuentesList) . '*';
            $parts[] = '';
        }
        if ($this->isDespidoQuery($pregunta)) {
            $parts[] = 'Asesoría: Juez de Trabajo o SITRACABAÑA — contacto@sitra-lacabana.org';
        } else {
            $parts[] = 'Asesoría: contacto@sitra-lacabana.org';
        }

        return implode("\n", array_filter($parts));
    }

    /**
     * Construye el contexto para el LLM con citas explícitas (Art., Cláusula).
     */
    private function buildContextForLlm(array $results): string
    {
        $parts = [];
        $seenKeys = [];
        $idx = 1;

        foreach ($results as $r) {
            $md = $r['metadata'] ?? [];
            $source = $md['source'] ?? '';
            $refType = $md['ref_type'] ?? '';
            $refNum = $md['ref_num'] ?? '';
            $ref = trim($refType . ' ' . $refNum);
            $docName = $this->getDocName($source);
            $key = $docName . '|' . $ref;
            if (in_array($key, $seenKeys, true)) {
                continue;
            }
            $text = trim($r['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $seenKeys[] = $key;
            $refLabel = $this->formatRefLabel($ref, $docName);
            $citeKey = $refNum !== ''
                ? ($source === 'codigo_trabajo' ? "Art. {$refNum} (Código de Trabajo)" : "Cláusula {$refNum} (Contrato Colectivo SITRACABAÑA)")
                : $docName;
            $parts[] = "[FRAGMENTO {$idx}] [CITA OBLIGATORIA: {$citeKey}]\n{$refLabel} - {$docName}\n\n{$text}";
            $idx++;
        }

        return implode("\n\n---\n\n", array_slice($parts, 0, 6));
    }

    /**
     * Verifica si la respuesta del LLM contiene al menos una cita del contexto (Art. X o Cláusula Y).
     */
    private function responseHasValidCitation(string $respuesta, array $results): bool
    {
        foreach ($results as $r) {
            $md = $r['metadata'] ?? [];
            $refNum = (string)($md['ref_num'] ?? '');
            $source = $md['source'] ?? '';
            if ($refNum === '') {
                continue;
            }
            if ($source === 'codigo_trabajo' && preg_match('/\bart\.?\s*' . preg_quote($refNum, '/') . '\b/ui', $respuesta)) {
                return true;
            }
            if ($source === 'cct_sitracabana' && preg_match('/\bcl[aá]usula\s*' . preg_quote($refNum, '/') . '\b/ui', $respuesta)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sintetiza la respuesta usando el LLM.
     */
    private function synthesizeWithLlm(string $pregunta, array $results): ?string
    {
        $config = require (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/config/app.php';
        $apiKey = $config['defensor_llm_api_key'] ?? null;
        if (!is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $context = $this->buildContextForLlm($results);
        if ($context === '') {
            return null;
        }

        $systemPrompt = 'Eres un ASESOR SINDICAL experto que representa a los trabajadores de SITRACABAÑA (Ingenio La Cabaña). Tu rol es dar orientación con PRECISIÓN LEGAL y TRAZABILIDAD de fuentes.' . "\n\n"
            . 'REGLAS ANTI-ALUCINACIÓN (OBLIGATORIAS):' . "\n"
            . '- **PROHIBIDO** inventar, inferir o extrapolar información que NO esté explícita en el contexto.' . "\n"
            . '- **PROHIBIDO** citar Art. X o Cláusula Y que no aparezcan en el contexto. Solo cita las fuentes listadas en [CITA OBLIGATORIA: ...].' . "\n"
            . '- **PROHIBIDO** dar números, plazos o montos que no figuren literalmente en el contexto.' . "\n"
            . '- Si un fragmento NO responde la pregunta, NO lo uses. Responde solo con lo que el contexto permita.' . "\n\n"
            . 'TRAZABILIDAD Y CITAS (OBLIGATORIAS):' . "\n"
            . '- **Cada afirmación legal** debe ir acompañada de su fuente: "Según Art. X del Código de Trabajo..." o "La Cláusula Y del Contrato Colectivo establece...".' . "\n"
            . '- **Al final** incluye la línea: "Fuentes: Art. X CT, Cláusula Y CC" con las normas que efectivamente citaste.' . "\n"
            . '- Prioriza Contrato Colectivo SITRACABAÑA antes que Código de Trabajo.' . "\n\n"
            . 'Si NO hay información relevante en el contexto: responde "No encontré información sobre esto en los documentos." y recomienda: contacto@sitra-lacabana.org' . "\n"
            . 'Responde siempre en español.' . "\n\n"
            . 'ESTRUCTURA DE TU RESPUESTA:' . "\n"
            . '1. **Respuesta directa**: Responde la pregunta en 1-2 oraciones.' . "\n"
            . '2. **Base legal**: Cita primero Cláusula Y (Contrato Colectivo) si aplica, luego Art. X (Código de Trabajo).' . "\n"
            . '3. **Sugerencias**: Qué puede hacer el trabajador, pasos recomendados, a quién acudir.' . "\n"
            . '4. **Fuentes**: Al final, lista las fuentes citadas (ej: "Fuentes: Art. 58 CT, Cláusula 55 CC").' . "\n\n"
            . 'EJEMPLOS DE RESPUESTAS BIEN FORMADAS:' . "\n\n"
            . '--- Ejemplo 1 (despido) ---' . "\n"
            . 'Pregunta: "Me despidieron sin aviso, ¿qué me corresponde?"' . "\n"
            . 'Respuesta: "Si te despidieron sin causa justificada ni preaviso, tienes derecho a indemnización. Según la **Cláusula 7 del Contrato Colectivo SITRACABAÑA** (estabilidad laboral) y el **Art. 58 del Código de Trabajo**, el patrono debe pagar indemnización. Te sugiero: 1) Solicitar por escrito el finiquito; 2) Acudir al sindicato SITRACABAÑA. Fuentes: Cláusula 7 CC, Art. 58 CT."' . "\n\n"
            . '--- Ejemplo 2 (vacaciones) ---' . "\n"
            . 'Pregunta: "¿Cuántos días de vacaciones me corresponden?"' . "\n"
            . 'Respuesta: "Según la **Cláusula 55 del Contrato Colectivo SITRACABAÑA**, los trabajadores tienen derecho a vacaciones según lo establecido en el Código de Trabajo. Te recomiendo revisar tu antigüedad y exigir el goce de vacaciones remuneradas. Si tienes dudas, contacta al sindicato. Fuentes: Cláusula 55 CC."' . "\n\n"
            . '--- Ejemplo 3 (sin información) ---' . "\n"
            . 'Pregunta: "¿Qué dice la ley sobre teletrabajo?"' . "\n"
            . 'Respuesta: "No encontré información sobre teletrabajo en el Código de Trabajo ni en el Contrato Colectivo SITRACABAÑA. Te recomiendo contactar al sindicato para asesoría personalizada: contacto@sitra-lacabana.org"' . "\n\n"
            . '--- Ejemplo 4 (Contrato Colectivo sin cláusulas) ---' . "\n"
            . 'Pregunta: "¿Qué dice el contrato sobre bonos?"' . "\n"
            . 'Respuesta: "No encontré cláusulas en el Contrato Colectivo SITRACABAÑA sobre este tema. La información que encontré proviene del Código de Trabajo: [resumen si hay]. Para consultas específicas del Contrato Colectivo: contacto@sitra-lacabana.org"';

        $hasCC = $this->hasContratoColectivoChunks($results);
        $ccNote = ($this->isContratoColectivoQuery($pregunta) && !$hasCC)
            ? "\n\nIMPORTANTE: El contexto NO contiene cláusulas del Contrato Colectivo sobre este tema. Indícalo explícitamente al usuario."
            : '';

        $despidoNote = $this->isDespidoQuery($pregunta)
            ? "\n\nIMPORTANTE PARA DESPIDO: Estructura tu respuesta en DOS BLOQUES: 1) PRIMERO lo que dice el Contrato Colectivo SITRACABAÑA (Cláusula 7 u otras); 2) DESPUÉS lo que dice el Código de Trabajo (Arts. 55, 58, 59, etc.)."
            : '';
        $aguinaldoNote = $this->isAguinaldoQuery($pregunta)
            ? "\n\nIMPORTANTE PARA AGUINALDO: Estructura tu respuesta en DOS BLOQUES: 1) PRIMERO lo que dice el Contrato Colectivo SITRACABAÑA (Cláusula 56 - AGUINALDOS); 2) DESPUÉS lo que dice el Código de Trabajo (Arts. 196-200)."
            : '';

        $userPrompt = "CONTEXTO (cada fragmento tiene [CITA OBLIGATORIA] - solo puedes citar estas fuentes exactas):\n\n{$context}\n\n---\n\nCONSULTA DEL TRABAJADOR: {$pregunta}{$ccNote}{$despidoNote}{$aguinaldoNote}\n\nResponde con precisión legal. Cita SOLO las fuentes del contexto. Cada afirmación debe tener su Art. X o Cláusula Y. Al final incluye: Fuentes: Art. X CT, Cláusula Y CC.";

        $client = new LlmClient(
            $apiKey,
            $config['defensor_llm_provider'] ?? 'groq',
            $config['defensor_llm_model'] ?? 'llama-3.1-8b-instant',
        );

        $response = $client->chat(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            1200,
            0.1
        );

        if ($response === null || mb_strlen($response) < 20) {
            return null;
        }
        return $response;
    }

    /**
     * Detecta saludos o mensajes casuales que no requieren búsqueda.
     */
    private function isGreetingOrCasual(string $pregunta): bool
    {
        $q = mb_strtolower(trim($pregunta));
        if (mb_strlen($q) > 50) {
            return false;
        }
        $greetings = [
            'hola', 'buenos días', 'buenas tardes', 'buenas noches', 'buen día',
            'qué tal', 'qué tal?', 'cómo estás', 'cómo está', 'qué onda',
            'hola!', 'hola?', 'buenas', 'saludos', 'hey', 'hi', 'hello',
            'buen día', 'buen dia', 'buenas', 'buenos dias', 'buenas tardes',
            'bienvenido', 'buen día', 'buen dia',
        ];
        foreach ($greetings as $g) {
            if ($q === $g || str_starts_with($q, $g . ' ') || str_starts_with($q, $g . '?')) {
                return true;
            }
        }
        if ((bool) preg_match('/^(hola|buenas|buenos|qu[eé]\s+tal|c[oó]mo\s+est[aá]s?|hey|hi|hello)[\s!?]*$/ui', $q)) {
            return true;
        }
        if ((bool) preg_match('/^(gracias|muchas gracias|adi[oó]s|chao|bye|nos vemos)[\s!?]*$/ui', $q)) {
            return true;
        }
        if ((bool) preg_match('/^(ayuda|help|qu[eé]\s+puedes\s+hacer|qu[eé]\s+haces)[\s!?]*$/ui', $q)) {
            return true;
        }
        return false;
    }

    private function getGreetingResponse(string $pregunta): string
    {
        $q = mb_strtolower(trim($pregunta));
        if ((bool) preg_match('/^(gracias|muchas gracias)/ui', $q)) {
            return '¡De nada! Si tienes más dudas sobre tus derechos laborales, aquí estaré para ayudarte.';
        }
        if ((bool) preg_match('/^(adi[oó]s|chao|bye|nos vemos)/ui', $q)) {
            return '¡Hasta pronto! Recuerda que puedes contactar al sindicato en contacto@sitra-lacabana.org si necesitas asesoría personalizada.';
        }
        if ((bool) preg_match('/^(ayuda|help|qu[eé]\s+puedes\s+hacer|qu[eé]\s+haces)/ui', $q)) {
            return 'Soy el Defensor Laboral IA. Puedo ayudarte con consultas sobre el Código de Trabajo de El Salvador y el Contrato Colectivo de SITRACABAÑA. Por ejemplo: vacaciones, aguinaldo, despido, indemnización, finiquito, preaviso, cese, bonificación, salario, horas extras, días de descanso, etc. ¿Qué te gustaría saber?';
        }
        return '¡Hola! Soy el Defensor Laboral IA. ¿En qué puedo ayudarte hoy? Puedo responder tus dudas sobre el Código de Trabajo y el Contrato Colectivo de SITRACABAÑA (Ingenio La Cabaña). Escribe tu consulta, por ejemplo: vacaciones, aguinaldo, despido, finiquito, bonificación, etc.';
    }

    /**
     * Detecta si el usuario pide calcular su aguinaldo.
     */
    private function isAguinaldoCalcRequest(string $pregunta): bool
    {
        $q = mb_strtolower($pregunta);
        return (bool) preg_match('/\b(calcula|calcular|calcule|cu[aá]nto\s+me\s+corresponde|cu[aá]nto\s+ser[ií]a)\s+(mi\s+)?aguinaldo\b/ui', $q)
            || preg_match('/\b(aguinaldo|d[eé]cimo)\s*[:\s]?\s*(calcula|calcular|cu[aá]nto)\b/ui', $q)
            || preg_match('/\bquiero\s+que\s+me\s+(calcule|calcules)\s+el\s+aguinaldo\b/ui', $q);
    }

    /**
     * Indica si el último mensaje del bot pedía datos para cálculo de aguinaldo.
     */
    private function lastBotAskedAguinaldoData(array $historial): bool
    {
        for ($i = count($historial) - 1; $i >= 0; $i--) {
            $m = $historial[$i] ?? [];
            $rol = $m['rol'] ?? $m['role'] ?? '';
            if (strtolower($rol) !== 'bot' && strtolower($rol) !== 'assistant') {
                continue;
            }
            $texto = mb_strtolower($m['contenido'] ?? $m['content'] ?? '');
            return (bool) (str_contains($texto, 'salario') && (str_contains($texto, 'antigüedad') || str_contains($texto, 'antiguedad')) && str_contains($texto, 'aguinaldo'));
        }
        return false;
    }

    /**
     * Intenta extraer salario, antigüedad y si es SITRACABAÑA del mensaje del usuario.
     * @return array{salario: float, meses: int, sitracabana: bool}|null
     */
    private function parseAguinaldoData(string $pregunta): ?array
    {
        $q = $pregunta;
        $salario = null;
        $meses = null;
        $sitracabana = null;

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:d[oó]lares?|\$|usd)/ui', $q, $m)) {
            $salario = (float) $m[1];
        } elseif (preg_match('/salario\s*(?:es\s*)?[:\s]*(\d+(?:\.\d+)?)/ui', $q, $m)) {
            $salario = (float) $m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:mensual|al\s+mes)/ui', $q, $m)) {
            $salario = (float) $m[1];
        } elseif (preg_match('/^(\d+(?:\.\d+)?)\s*[,y]\s*/u', $q, $m)) {
            $salario = (float) $m[1];
        } elseif (preg_match('/(\d{3,})\b/u', $q, $m)) {
            $salario = (float) $m[1];
        }

        if (preg_match('/(\d+)\s*a[nñ]os?\s*(?:y\s*)?(\d+)\s*meses?/ui', $q, $m)) {
            $meses = (int) $m[1] * 12 + (int) $m[2];
        } elseif (preg_match('/(\d+)\s*a[nñ]os?/ui', $q, $m)) {
            $meses = (int) $m[1] * 12;
        } elseif (preg_match('/(\d+)\s*meses?/ui', $q, $m)) {
            $meses = (int) $m[1];
        } elseif (preg_match('/antig[uü]edad\s*(?:es\s*)?[:\s]*(\d+)/ui', $q, $m)) {
            $meses = (int) $m[1] * 12;
        } elseif (preg_match('/[,y]\s*(\d+)\s*(?:a[nñ]os?|años)/ui', $q, $m)) {
            $meses = (int) $m[1] * 12;
        } elseif (preg_match('/\b(\d+)\s*(?:a[nñ]os?|años)/ui', $q, $m)) {
            $meses = (int) $m[1] * 12;
        } elseif (preg_match('/medio\s*a[nñ]o|a[nñ]o\s*y\s*medio/ui', $q, $m)) {
            $meses = 18;
        }

        if (preg_match('/\b(s[ií]|si|yes|trabajo\s+ah[ií]|soy\s+de\s+ah[ií])\b/ui', $q) && (str_contains($q, 'sitracaba') || str_contains($q, 'ingenio') || str_contains($q, 'cabaña') || str_contains($q, 'cabaña'))) {
            $sitracabana = true;
        } elseif (preg_match('/\b(no|nop)\b/ui', $q) && (str_contains($q, 'sitracaba') || str_contains($q, 'ingenio') || strlen($q) < 50)) {
            $sitracabana = false;
        } elseif (str_contains(mb_strtolower($q), 'sitracaba') || str_contains(mb_strtolower($q), 'ingenio la cabaña')) {
            $sitracabana = !preg_match('/\bno\b/u', $q);
        } elseif (preg_match('/[,y]\s*(s[ií]|si)\s*$/ui', $q) || preg_match('/^\s*(s[ií]|si)\s*$/ui', $q)) {
            $sitracabana = true;
        } elseif (preg_match('/[,y]\s*no\s*$/ui', $q) || preg_match('/^\s*no\s*$/ui', $q)) {
            $sitracabana = false;
        } else {
            $sitracabana = false;
        }

        if ($salario !== null && $salario > 0 && $meses !== null && $meses >= 0) {
            return ['salario' => $salario, 'meses' => min($meses, 120), 'sitracabana' => $sitracabana ?? false];
        }
        return null;
    }

    /**
     * Calcula el aguinaldo según Código de Trabajo y Contrato Colectivo SITRACABAÑA.
     * CT: 1-3 años=15 días, 3-10=19 días, 10+=21 días. CC: +5 días.
     */
    private function calculateAguinaldo(float $salarioMensual, int $mesesTrabajados, bool $esSitracabana): array
    {
        $anios = $mesesTrabajados / 12.0;
        $diasBase = match (true) {
            $anios >= 10 => 21,
            $anios >= 3 => 19,
            default => 15,
        };
        if ($esSitracabana) {
            $diasBase += 5;
        }
        $proporcion = $mesesTrabajados >= 12 ? 1.0 : $mesesTrabajados / 12.0;
        $diasDebidos = $diasBase * $proporcion;
        $salarioDiario = $salarioMensual / 30.0;
        $monto = round($salarioDiario * $diasDebidos, 2);

        return [
            'dias' => round($diasDebidos, 1),
            'monto' => $monto,
            'dias_base' => $diasBase,
            'proporcion' => $proporcion,
        ];
    }

    /**
     * Respuesta con las preguntas para recopilar datos del aguinaldo.
     */
    private function getAguinaldoQuestionsResponse(): string
    {
        return "Para calcular tu aguinaldo necesito la siguiente información:\n\n"
            . "1. **Tu salario mensual básico** (en dólares). Ejemplo: 500\n"
            . "2. **Tu antigüedad** (años trabajados, o meses si llevas menos de un año). Ejemplo: 3 años\n"
            . "3. **¿Eres trabajador de Ingenio La Cabaña / SITRACABAÑA?** (sí o no). El Contrato Colectivo otorga 5 días adicionales.\n\n"
            . "Puedes responder en un solo mensaje, por ejemplo: *\"500 dólares, 3 años, sí\"* o *\"mi salario es 600, tengo 5 años, trabajo en el ingenio\"*.";
    }

    /**
     * Procesa una consulta y devuelve la respuesta completa.
     * @param array $historial Últimos mensajes [{rol, contenido}] para contexto conversacional
     * @return array{success: bool, respuesta: string, fuentes: array}
     */
    public function consulta(string $pregunta, array $historial = []): array
    {
        $pregunta = trim($pregunta);
        if ($pregunta === '') {
            return [
                'success' => false,
                'message' => "Falta el campo 'pregunta'",
                'respuesta' => '',
                'fuentes' => [],
            ];
        }
        if (mb_strlen($pregunta) > 500) {
            return [
                'success' => false,
                'message' => 'La pregunta es demasiado larga',
                'respuesta' => '',
                'fuentes' => [],
            ];
        }

        if ($this->isGreetingOrCasual($pregunta)) {
            return [
                'success' => true,
                'respuesta' => $this->getGreetingResponse($pregunta),
                'fuentes' => [],
            ];
        }

        if ($this->isAguinaldoContratoQuery($pregunta)) {
            $clauseChunks = $this->getClauseByNumber(56);
            if ($clauseChunks !== []) {
                $textos = array_map(fn($c) => trim($c['text'] ?? ''), $clauseChunks);
                $textoCompleto = implode("\n\n", array_filter($textos));
                $resp = "**Cláusula 56 – AGUINALDOS** – Contrato Colectivo SITRACABAÑA (Ingenio La Cabaña)\n\n"
                    . $this->trimText($textoCompleto, 2500);
                if (mb_strlen($textoCompleto) > 2500) {
                    $resp .= "\n\n*… (texto recortado). Para el texto completo: contacto@sitra-lacabana.org*";
                }
                return [
                    'success' => true,
                    'respuesta' => $resp,
                    'fuentes' => [['source' => 'Contrato Colectivo SITRACABAÑA', 'ref' => 'Cláusula 56']],
                ];
            }
        }

        $clauseNum = $this->parseClauseNumber($pregunta);
        if ($clauseNum !== null) {
            $clauseChunks = $this->getClauseByNumber($clauseNum);
            if ($clauseChunks !== []) {
                $textos = array_map(fn($c) => trim($c['text'] ?? ''), $clauseChunks);
                $textoCompleto = implode("\n\n", array_filter($textos));
                $resp = "**Cláusula {$clauseNum}** – Contrato Colectivo SITRACABAÑA (Ingenio La Cabaña)\n\n"
                    . $this->trimText($textoCompleto, 2500);
                if (mb_strlen($textoCompleto) > 2500) {
                    $resp .= "\n\n*… (texto recortado). Para el texto completo: contacto@sitra-lacabana.org*";
                }
                return [
                    'success' => true,
                    'respuesta' => $resp,
                    'fuentes' => [['source' => 'Contrato Colectivo SITRACABAÑA', 'ref' => "Cláusula {$clauseNum}"]],
                ];
            }
            return [
                'success' => true,
                'respuesta' => "**La Cláusula {$clauseNum} no existe** en el Contrato Colectivo SITRACABAÑA (Ingenio La Cabaña).\n\nVerifica que el número sea correcto. Si necesitas asesoría sobre alguna cláusula en particular, contacta al sindicato: contacto@sitra-lacabana.org",
                'fuentes' => [],
            ];
        }

        $articleNum = $this->parseArticleNumber($pregunta);
        if ($articleNum !== null) {
            $articleChunks = $this->getArticleByNumber($articleNum);
            if ($articleChunks !== []) {
                $textos = array_map(fn($c) => trim($c['text'] ?? ''), $articleChunks);
                $textoCompleto = implode("\n\n", array_filter($textos));
                $resp = "**Art. {$articleNum}** – Código de Trabajo (El Salvador)\n\n"
                    . $this->trimText($textoCompleto, 2500);
                if (mb_strlen($textoCompleto) > 2500) {
                    $resp .= "\n\n*… (texto recortado). Para el texto completo: contacto@sitra-lacabana.org*";
                }
                return [
                    'success' => true,
                    'respuesta' => $resp,
                    'fuentes' => [['source' => 'Código de Trabajo', 'ref' => "Art. {$articleNum}"]],
                ];
            }
            return [
                'success' => true,
                'respuesta' => "**El Artículo {$articleNum} no existe** en el Código de Trabajo de El Salvador.\n\nVerifica que el número sea correcto. Si necesitas asesoría sobre algún artículo en particular, contacta al sindicato: contacto@sitra-lacabana.org",
                'fuentes' => [],
            ];
        }

        $esPeticionAguinaldo = $this->isAguinaldoCalcRequest($pregunta);
        $ultimoPidioDatos = $this->lastBotAskedAguinaldoData($historial);
        if ($esPeticionAguinaldo || $ultimoPidioDatos) {
            $datos = $this->parseAguinaldoData($pregunta);
            if ($datos !== null) {
                $calc = $this->calculateAguinaldo($datos['salario'], $datos['meses'], $datos['sitracabana']);
                $resp = "Según tu información, tu **aguinaldo** sería aproximadamente:\n\n"
                    . "**" . number_format($calc['monto'], 2) . " dólares**\n\n"
                    . "Detalle:\n"
                    . "- Días de aguinaldo: " . $calc['dias'] . " días\n"
                    . "- Salario diario: $" . number_format($datos['salario'] / 30, 2) . "\n"
                    . "- Base legal: " . $calc['dias_base'] . " días ("
                    . ($datos['sitracabana'] ? "Contrato Colectivo SITRACABAÑA, Cláusula 56" : "Código de Trabajo Art. 198")
                    . ")\n\n"
                    . "*Referencia: Código de Trabajo Arts. 196-199; Contrato Colectivo Cláusula 56 (5 días adicionales para SITRACABAÑA).*";
                return [
                    'success' => true,
                    'respuesta' => $resp,
                    'fuentes' => [
                        ['source' => 'Código de Trabajo', 'ref' => 'Art. 196-199'],
                        ['source' => 'Contrato Colectivo SITRACABAÑA', 'ref' => 'Cláusula 56'],
                    ],
                ];
            }
            return [
                'success' => true,
                'respuesta' => $this->getAguinaldoQuestionsResponse(),
                'fuentes' => [],
            ];
        }

        // Para despido y aguinaldo usamos el flujo PHP (chunks específicos, Contrato Colectivo primero).
        // LangChain/FAISS puede priorizar Código de Trabajo sobre Contrato Colectivo.
        if (!$this->isDespidoQuery($pregunta) && !$this->isAguinaldoQuery($pregunta)) {
            $langChainResult = $this->consultaViaLangChain($pregunta);
            if ($langChainResult !== null) {
                return $langChainResult;
            }
        }

        $results = $this->search($pregunta);
        $respuestaFormateada = $this->formatResponse($results, $pregunta);

        $respuesta = null;
        try {
            $respuesta = $this->synthesizeWithLlm($pregunta, $results);
        } catch (\Throwable $e) {
            error_log('[DefensorService] LLM falló: ' . $e->getMessage());
        }
        if ($respuesta === null) {
            $respuesta = $respuestaFormateada;
        } elseif ($results !== [] && !$this->responseHasValidCitation($respuesta, $results)) {
            $respuesta = $respuestaFormateada;
        } else {
            $respuestaLower = mb_strtolower($respuesta);
            if (preg_match('/no\s+(hay|encontr[eé]|menciona|existe)\s+informaci[oó]n|no\s+se\s+menciona/i', $respuestaLower)
                && $results !== []) {
                $respuesta = $respuestaFormateada;
            } elseif (!str_contains($respuestaLower, 'contacto@')) {
                $respuesta .= "\n\n*Asesoría: contacto@sitra-lacabana.org*";
            }
        }

        $fuentes = [];
        foreach (array_slice($results, 0, 3) as $r) {
            $md = $r['metadata'] ?? [];
            $ref = trim(($md['ref_type'] ?? '') . ' ' . ($md['ref_num'] ?? ''));
            $fuentes[] = [
                'source' => $this->getDocName($md['source'] ?? ''),
                'ref' => $ref,
            ];
        }

        return [
            'success' => true,
            'respuesta' => $respuesta,
            'fuentes' => $fuentes,
        ];
    }
}
