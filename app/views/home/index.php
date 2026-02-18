<?php
/** @var array<int,array<string,mixed>> $news */
/** @var array<int,array<string,mixed>> $gallery */
/** @var array<int,array<string,mixed>> $documents */
/** @var array<int,array<string,mixed>> $board */
?>

<section class="hero text-light">
    <div class="container py-5">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-6">
                <div class="d-inline-flex align-items-center gap-2 hero-kicker rounded-pill px-3 py-2 mb-3">
                    <img src="<?= e(site_logo_url()) ?>" alt="Logo" width="26" height="26" class="bg-white rounded-2 p-1">
                    <span class="small">Sitio oficial</span>
                </div>
                <h1 class="display-6 fw-bold mb-2">
                    <span class="hero-title">SITRACABAÑA</span><br>
                    <span class="hero-subtitle">Sindicato de Trabajadores · Ingenio La Cabaña</span>
                </h1>
                <p class="lead text-white mb-4">
                    Organización, representación y defensa de los derechos laborales con enfoque humano y transparente.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-danger btn-lg fw-semibold" href="<?= e(url('/#afiliacion')) ?>">
                        <i class="bi bi-person-plus me-1"></i> Afíliate ahora
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="<?= e(url('/#nosotros')) ?>">
                        <i class="bi bi-info-circle me-1"></i> Conócenos
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="hero-card p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fw-semibold">Compromiso sindical</div>
                        <span class="badge hero-badge">2026</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="mini-stat">
                                <i class="bi bi-shield-check"></i>
                                <div class="fw-semibold">Defensa</div>
                                <div class="text-secondary small">Acompañamiento laboral</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="mini-stat">
                                <i class="bi bi-people"></i>
                                <div class="fw-semibold">Unidad</div>
                                <div class="text-secondary small">Trabajo colectivo</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="mini-stat">
                                <i class="bi bi-journal-check"></i>
                                <div class="fw-semibold">Gestión</div>
                                <div class="text-secondary small">Transparencia y orden</div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="small text-secondary">
                        <i class="bi bi-megaphone me-1"></i> Mantente al día con comunicados, documentos y actividades.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container my-4">
    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success border-0 shadow-sm"><?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger border-0 shadow-sm"><?= e($flash_error) ?></div>
    <?php endif; ?>
</div>

<section id="nosotros" class="section">
    <div class="container">
        <div class="section-title">
            <h2 class="h3 fw-bold mb-1"><?= e(isset($about_section['title']) && $about_section['title'] !== '' ? $about_section['title'] : 'Nosotros') ?></h2>
            <p class="text-secondary mb-0"><?= e(isset($about_section['subtitle']) && $about_section['subtitle'] !== '' ? $about_section['subtitle'] : 'Nuestra razón de ser: servicio, justicia y dignidad.') ?></p>
        </div>
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="icon-pill bg-primary-subtle text-primary mb-3"><i class="bi bi-bullseye"></i></div>
                        <h3 class="h5 fw-semibold"><?= e(isset($about_section['mission']['title']) && $about_section['mission']['title'] !== '' ? $about_section['mission']['title'] : 'Misión') ?></h3>
                        <p class="text-secondary mb-0">
                            <?= e(isset($about_section['mission']['content']) && $about_section['mission']['content'] !== '' ? $about_section['mission']['content'] : 'Representar y proteger a las y los trabajadores del Ingenio La Cabaña, promoviendo condiciones laborales justas, seguridad, bienestar y respeto a los derechos.') ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="icon-pill bg-danger-subtle text-danger mb-3"><i class="bi bi-eye"></i></div>
                        <h3 class="h5 fw-semibold"><?= e(isset($about_section['vision']['title']) && $about_section['vision']['title'] !== '' ? $about_section['vision']['title'] : 'Visión') ?></h3>
                        <p class="text-secondary mb-0">
                            <?= e(isset($about_section['vision']['content']) && $about_section['vision']['content'] !== '' ? $about_section['vision']['content'] : 'Ser un sindicato moderno, transparente y participativo, referente por su gestión y por el impacto positivo en la vida de la base trabajadora.') ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="icon-pill bg-warning-subtle text-warning mb-3"><i class="bi bi-clock-history"></i></div>
                        <h3 class="h5 fw-semibold"><?= e(isset($about_section['history']['title']) && $about_section['history']['title'] !== '' ? $about_section['history']['title'] : 'Historia') ?></h3>
                        <p class="text-secondary mb-0">
                            <?= e(isset($about_section['history']['content']) && $about_section['history']['content'] !== '' ? $about_section['history']['content'] : 'Nacemos de la necesidad de organizarnos para dialogar, negociar y construir acuerdos, priorizando la unidad y el respeto. Crecemos con el trabajo diario y el compromiso de cada afiliado.') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="noticias" class="section section-alt">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h3 fw-bold mb-1"><?= e($noticias_title ?? 'Noticias') ?></h2>
                <p class="text-secondary mb-0"><?= e($noticias_subtitle ?? 'Comunicados y novedades del sindicato.') ?></p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('/noticias')) ?>">
                Ver todas <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($news)): ?>
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Aún no hay noticias publicadas.</div>
                </div>
            <?php else: ?>
                <?php foreach ($news as $n): ?>
                    <?php
                    $img = !empty($n['image_url']) ? (string)$n['image_url'] : (!empty($n['image_path']) ? (string)$n['image_path'] : '/img/placeholder-news.svg');
                    $imgSrc = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset($img);
                    $date = !empty($n['date']) ? (string)$n['date'] : (!empty($n['published_at']) ? date('d/m/Y', strtotime((string)$n['published_at'])) : '');
                    $extUrl = !empty($n['url']) ? (string)$n['url'] : null;
                    $source = !empty($n['source']) ? (string)$n['source'] : '';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden<?= $extUrl ? ' card-link' : '' ?>">
                            <?php if ($extUrl): ?>
                                <a href="<?= e($extUrl) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-body">
                                    <img src="<?= e($imgSrc) ?>" class="card-img-top news-img" alt="<?= e((string)$n['title']) ?>">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                            <span class="badge text-bg-primary"><i class="bi bi-newspaper me-1"></i> Noticia</span>
                                            <?php if ($source): ?><span class="badge text-bg-secondary"><?= e($source) ?></span><?php endif; ?>
                                            <?php if ($date !== ''): ?><span class="text-secondary small"><?= e($date) ?></span><?php endif; ?>
                                        </div>
                                        <h3 class="h5 fw-semibold mb-2"><?= e((string)$n['title']) ?></h3>
                                        <p class="text-secondary mb-0"><?= e((string)($n['excerpt'] ?? '')) ?></p>
                                        <span class="small text-primary mt-2 d-inline-block">Ver noticia completa <i class="bi bi-box-arrow-up-right"></i></span>
                                    </div>
                                </a>
                            <?php else: ?>
                                <img src="<?= e($imgSrc) ?>" class="card-img-top news-img" alt="<?= e((string)$n['title']) ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge text-bg-primary"><i class="bi bi-newspaper me-1"></i> Noticia</span>
                                        <?php if ($date !== ''): ?><span class="text-secondary small"><?= e($date) ?></span><?php endif; ?>
                                    </div>
                                    <h3 class="h5 fw-semibold mb-2"><?= e((string)$n['title']) ?></h3>
                                    <p class="text-secondary mb-0"><?= e((string)($n['excerpt'] ?? '')) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="beneficios" class="section">
    <div class="container">
        <div class="section-title">
            <h2 class="h3 fw-bold mb-1">Beneficios</h2>
            <p class="text-secondary mb-0">Acompañamiento real, beneficios claros.</p>
        </div>
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-briefcase"></i>
                    <div class="fw-semibold">Gestión laboral</div>
                    <div class="text-secondary small">Asesoría y acompañamiento en trámites.</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-heart-pulse"></i>
                    <div class="fw-semibold">Bienestar</div>
                    <div class="text-secondary small">Apoyo en salud y actividades de integración.</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-shield-check"></i>
                    <div class="fw-semibold">Protección</div>
                    <div class="text-secondary small">Defensa de derechos y seguimiento de casos.</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-mortarboard"></i>
                    <div class="fw-semibold">Capacitación</div>
                    <div class="text-secondary small">Talleres, formación y orientación.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="galeria" class="section section-alt">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h3 fw-bold mb-1">Galería</h2>
                <p class="text-secondary mb-0">Actividades, asambleas y momentos importantes.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('/galeria')) ?>">Ver galería</a>
        </div>

        <div class="row g-3">
            <?php if (empty($gallery)): ?>
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Aún no hay fotos en la galería.</div>
                </div>
            <?php else: ?>
                <?php foreach ($gallery as $g): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a class="gallery-tile shadow-sm" href="<?= e(asset((string)$g['image_path'])) ?>" data-gallery="open" data-title="<?= e((string)$g['title']) ?>">
                            <img src="<?= e(asset((string)$g['image_path'])) ?>" alt="<?= e((string)$g['title']) ?>" loading="lazy">
                            <span class="gallery-caption"><?= e((string)$g['title']) ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="documentos" class="section">
    <div class="container">
        <div class="section-title">
            <h2 class="h3 fw-bold mb-1">Documentos</h2>
            <p class="text-secondary mb-0">PDFs oficiales disponibles para descarga.</p>
        </div>

        <div class="list-group shadow-sm">
            <?php if (empty($documents)): ?>
                <div class="list-group-item py-4">
                    <div class="text-secondary">Aún no hay documentos publicados.</div>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $d): ?>
                    <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3"
                       href="<?= e(asset((string)$d['file_path'])) ?>" download>
                        <div class="d-flex align-items-center gap-3">
                            <span class="doc-icon"><i class="bi bi-filetype-pdf"></i></span>
                            <div>
                                <div class="fw-semibold"><?= e((string)$d['title']) ?></div>
                                <div class="text-secondary small">Descargar PDF</div>
                            </div>
                        </div>
                        <i class="bi bi-download"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="directiva" class="section section-alt">
    <div class="container">
        <div class="section-title">
            <h2 class="h3 fw-bold mb-1"><?= e($directiva_title ?? 'Directiva') ?></h2>
            <p class="text-secondary mb-0"><?= e($directiva_subtitle ?? 'Equipo de trabajo y representación.') ?></p>
        </div>

        <div class="row g-4">
            <?php if (empty($board)): ?>
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Aún no hay directiva cargada.</div>
                </div>
            <?php else: ?>
                <?php foreach ($board as $m): ?>
                    <?php
                    $photo = !empty($m['photo_path']) ? (string)$m['photo_path'] : '/img/placeholder-person.svg';
                    $photoSrc = str_starts_with($photo, '/img/') ? asset($photo) : uploaded_asset_url($photo);
                    ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="<?= e($photoSrc) ?>" class="card-img-top person-img" alt="<?= e((string)$m['name']) ?>">
                            <div class="card-body p-4">
                                <div class="text-danger fw-semibold small mb-1"><?= e((string)$m['position']) ?></div>
                                <div class="fw-semibold"><?= e((string)$m['name']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="afiliacion" class="section">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-5">
                <div class="section-title mb-3">
                    <h2 class="h3 fw-bold mb-1">Afiliación</h2>
                    <p class="text-secondary mb-0">Completa el formulario y nos comunicamos contigo.</p>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex gap-3 mb-3">
                            <div class="icon-pill bg-danger-subtle text-danger"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <div class="fw-semibold">Confidencialidad</div>
                                <div class="text-secondary small">Tu información se usa solo para gestionar tu solicitud.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="icon-pill bg-primary-subtle text-primary"><i class="bi bi-chat-dots"></i></div>
                            <div>
                                <div class="fw-semibold">Respuesta</div>
                                <div class="text-secondary small">Te contactaremos por teléfono o correo.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form method="post" action="<?= e(url('/afiliacion')) ?>" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" name="name" required maxlength="120" placeholder="Tu nombre">
                                    <div class="invalid-feedback">Ingresa tu nombre.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="phone" required maxlength="40" placeholder="+502 ...">
                                    <div class="invalid-feedback">Ingresa tu teléfono.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required maxlength="120" placeholder="tu@email.com">
                                    <div class="invalid-feedback">Ingresa un email válido.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Mensaje</label>
                                    <textarea class="form-control" name="message" rows="4" required maxlength="1000" placeholder="Cuéntanos tu consulta o solicitud"></textarea>
                                    <div class="invalid-feedback">Escribe un mensaje.</div>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                    <button class="btn btn-danger btn-lg fw-semibold" type="submit">
                                        Enviar solicitud <i class="bi bi-send ms-1"></i>
                                    </button>
                                    <span class="text-secondary small">Al enviar, aceptas ser contactado por el sindicato.</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal simple para galería -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="galleryModalTitle">Imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <img id="galleryModalImg" src="" alt="" class="w-100 d-block">
            </div>
        </div>
    </div>
</div>

