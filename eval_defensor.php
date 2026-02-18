<?php
/**
 * Evaluación del Defensor Laboral IA
 * Mide cuántas veces se devuelven los artículos/cláusulas correctos.
 *
 * Ejecutar: php eval_defensor.php
 * O visitar: http://localhost/sitra_web/eval_defensor.php
 *
 * Usa data/eval_cases.json como conjunto de prueba.
 */
declare(strict_types=1);

define('BASE_PATH', __DIR__);
require BASE_PATH . '/app/core/Autoloader.php';
\App\Core\Autoloader::register(BASE_PATH . '/app');

$casesFile = BASE_PATH . '/data/eval_cases.json';
if (!is_file($casesFile)) {
    die("Error: No existe data/eval_cases.json\n");
}

$data = json_decode(file_get_contents($casesFile), true);
$cases = $data['cases'] ?? [];
if (empty($cases)) {
    die("Error: eval_cases.json no tiene casos\n");
}

$service = new \App\Services\DefensorService();
$service->loadChunks();

$results = [];
$byCategory = [];
$total = count($cases);
$passed = 0;
$passedNoRelevant = 0;

foreach ($cases as $case) {
    $id = $case['id'] ?? 'unknown';
    $pregunta = $case['pregunta'] ?? '';
    $expectedRefs = $case['expected_refs'] ?? [];
    $expectNoRelevant = (bool)($case['expect_no_relevant'] ?? false);
    $category = $case['category'] ?? 'otro';

    if (!isset($byCategory[$category])) {
        $byCategory[$category] = ['total' => 0, 'passed' => 0];
    }
    $byCategory[$category]['total']++;

    $searchResults = $service->search($pregunta);

    $returnedRefs = [];
    foreach ($searchResults as $r) {
        $md = $r['metadata'] ?? [];
        $source = $md['source'] ?? '';
        $refType = $md['ref_type'] ?? '';
        $refNum = $md['ref_num'] ?? null;
        if ($refNum !== null && $refNum !== '') {
            $key = ($source === 'codigo_trabajo' ? 'ART' : 'CLA') . '-' . $refNum;
            $returnedRefs[$key] = true;
        }
    }

    $hit = false;
    if ($expectNoRelevant) {
        $hit = empty($returnedRefs);
    } else {
        foreach ($expectedRefs as $ref) {
            if (isset($returnedRefs[$ref])) {
                $hit = true;
                break;
            }
        }
    }

    if ($hit) {
        $passed++;
        $byCategory[$category]['passed']++;
        if ($expectNoRelevant) {
            $passedNoRelevant++;
        }
    }

    $results[] = [
        'id' => $id,
        'category' => $category,
        'pregunta' => $pregunta,
        'expected' => $expectedRefs,
        'returned' => array_keys($returnedRefs),
        'hit' => $hit,
        'expect_no_relevant' => $expectNoRelevant,
    ];
}

$precision = $total > 0 ? round(100 * $passed / $total, 1) : 0;

$jsonOutput = (isset($_GET['format']) && $_GET['format'] === 'json')
    || (isset($argv[1]) && $argv[1] === '--json');

if ($jsonOutput) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'total' => $total,
        'passed' => $passed,
        'precision' => $precision,
        'by_category' => $byCategory,
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluación Defensor Laboral IA</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        h1 { color: #1a1a2e; }
        .score { font-size: 2rem; font-weight: bold; color: #0d7377; }
        .score.fail { color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f5f5f5; }
        .hit { color: #27ae60; }
        .miss { color: #c0392b; }
        .category { margin: 1rem 0; }
        .meta { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
<h1>Evaluación Defensor Laboral IA</h1>
<p class="meta"><?= $total ?> casos | <?= date('Y-m-d H:i') ?></p>

<h2>Resultado global</h2>
<p class="score <?= $precision >= 70 ? '' : 'fail' ?>"><?= $passed ?>/<?= $total ?> (<?= $precision ?>%)</p>
<p>Casos con al menos un Art./Cláusula esperado en los resultados.</p>

<h2>Por categoría</h2>
<?php foreach ($byCategory as $cat => $stats): ?>
<div class="category">
    <strong><?= htmlspecialchars($cat) ?></strong>:
    <?= $stats['passed'] ?>/<?= $stats['total'] ?>
    (<?= $stats['total'] > 0 ? round(100 * $stats['passed'] / $stats['total'], 0) : 0 ?>%)
</div>
<?php endforeach; ?>

<h2>Detalle por caso</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Categoría</th>
            <th>Pregunta</th>
            <th>Esperado</th>
            <th>Devuelto</th>
            <th>✓/✗</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td><?= htmlspecialchars(mb_substr($r['pregunta'], 0, 50)) ?>…</td>
            <td><?= htmlspecialchars(implode(', ', $r['expected']) ?: '—') ?></td>
            <td><?= htmlspecialchars(implode(', ', array_slice($r['returned'], 0, 5)) ?: '—') ?></td>
            <td class="<?= $r['hit'] ? 'hit' : 'miss' ?>"><?= $r['hit'] ? '✓' : '✗' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<p class="meta">Usa estos resultados para priorizar: chunking, patrones, embeddings, isNoiseChunk, etc.</p>
</body>
</html>
