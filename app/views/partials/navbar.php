<?php
$onHome = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/') === (base_url() . '/');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-sindicato fixed-top border-bottom border-dark-subtle">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('/')) ?>">
            <img src="<?= e(site_logo_url()) ?>" alt="Logo" width="34" height="34" class="rounded-2 bg-white p-1">
            <span class="fw-semibold"><?= e(APP_NAME !== '' ? APP_NAME : 'SITRACABAÑA') ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#nosotros')) ?>">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#noticias')) ?>">Noticias</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#beneficios')) ?>">Beneficios</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#galeria')) ?>">Galería</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#documentos')) ?>">Documentos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/#directiva')) ?>">Directiva</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-danger btn-sm fw-semibold" href="<?= e(url('/#afiliacion')) ?>">
                        <i class="bi bi-person-plus me-1"></i> Afíliate ahora
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

