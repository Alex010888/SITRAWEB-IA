<footer class="footer bg-dark text-light mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?= e(site_logo_url()) ?>" alt="Logo" width="34" height="34" class="rounded-2 bg-white p-1">
                    <div class="fw-semibold"><?= e(APP_NAME) ?></div>
                </div>
                <p class="text-light-emphasis mb-0">
                    Unidad, dignidad y defensa de los derechos laborales en el Ingenio La Cabaña.
                </p>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="fw-semibold mb-2">Contacto</div>
                <div class="text-light-emphasis small">
                    <div><i class="bi bi-geo-alt me-1"></i> Ingenio La Cabaña</div>
                    <div><i class="bi bi-telephone me-1"></i> +502 0000-0000</div>
                    <div><i class="bi bi-envelope me-1"></i> contacto@sitra-lacabana.org</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="fw-semibold mb-2">Redes</div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-light btn-sm" href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a class="btn btn-outline-light btn-sm" href="#" aria-label="X/Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a class="btn btn-outline-light btn-sm" href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a class="btn btn-outline-light btn-sm" href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <hr class="border-light opacity-25 my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-light-emphasis">
            <div>© <?= e((string)date('Y')) ?> <?= e(APP_NAME) ?>. Todos los derechos reservados.</div>
            <div>Hecho con PHP + Bootstrap 5.</div>
        </div>
    </div>
</footer>

