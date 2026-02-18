<?php
/** @var array<int,array<string,mixed>> $items */
?>
<section class="section">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Galería</h1>
                <p class="text-secondary mb-0">Fotos de actividades y eventos.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(url('/')) ?>">
                <i class="bi bi-arrow-left me-1"></i> Inicio
            </a>
        </div>

        <?php if (!empty($flash_success)): ?>
            <div class="alert alert-success border-0 shadow-sm"><?= e($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?= e($flash_error) ?></div>
        <?php endif; ?>

        <?php if (defined('PUBLIC_GALLERY_UPLOAD') && PUBLIC_GALLERY_UPLOAD === true): ?>
            <div class="accordion mb-4" id="accUpload">
                <div class="accordion-item border-0 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#uploadPanel" aria-expanded="false">
                            <i class="bi bi-cloud-upload me-2"></i> Subir imagen (admin)
                        </button>
                    </h2>
                    <div id="uploadPanel" class="accordion-collapse collapse" data-bs-parent="#accUpload">
                        <div class="accordion-body">
                            <form method="post" action="<?= e(url('/galeria/subir')) ?>" enctype="multipart/form-data" class="row g-3">
                                <?= csrf_field() ?>
                                <div class="col-12 col-md-5">
                                    <label class="form-label">Título</label>
                                    <input class="form-control" name="title" maxlength="120" placeholder="Ej: Asamblea general">
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label">Imagen</label>
                                    <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,.svg" required>
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-end">
                                    <button class="btn btn-danger w-100 fw-semibold" type="submit">Subir</button>
                                </div>
                                <div class="col-12">
                                    <div class="text-secondary small">Se guardará en `public/img/gallery` y se registrará en BD.</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Aún no hay fotos en la galería.</div>
                </div>
            <?php else: ?>
                <?php foreach ($items as $g): ?>
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

