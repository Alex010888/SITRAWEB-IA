<?php
/** @var string $__viewFile */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? ($title . ' | ' . APP_NAME) : APP_NAME) ?></title>
    <meta name="description" content="Sitio oficial del Sindicato de Trabajadores del Ingenio La Cabaña. Noticias, beneficios, documentos y afiliación.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_v('/css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_v('/css/chat-widget.css')) ?>" rel="stylesheet">
</head>
<body class="landing-page">

<?php require dirname(__DIR__) . '/partials/navbar.php'; ?>

<main class="pt-nav">
    <?php require $__viewFile; ?>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
<?php require dirname(__DIR__) . '/partials/chat_widget.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>window.DEFENSOR_IA_API_URL = '<?= e(url('/api/defensor/consulta')) ?>';</script>
<script src="<?= e(asset_v('/js/main.js')) ?>"></script>
<script src="<?= e(asset_v('/js/chat-widget.js')) ?>"></script>
</body>
</html>

