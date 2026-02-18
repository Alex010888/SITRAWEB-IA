<?php
/** @var array<int,array<string,mixed>> $items */
/** @var string $subtitle */
?>
<section class="section">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= e($title ?? 'Noticias') ?></h1>
                <p class="text-secondary mb-0"><?= e($subtitle ?? 'Comunicados y novedades.') ?></p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(url('/')) ?>">
                <i class="bi bi-arrow-left me-1"></i> Inicio
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0">Aún no hay noticias publicadas.</div>
                </div>
            <?php else: ?>
                <?php foreach ($items as $n): ?>
                    <?php
                    $img = !empty($n['image_url']) ? (string)$n['image_url'] : (!empty($n['image_path']) ? (string)$n['image_path'] : '/img/placeholder-news.svg');
                    $imgSrc = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset($img);
                    $date = !empty($n['date']) ? (string)$n['date'] : (!empty($n['published_at']) ? date('d/m/Y', strtotime((string)$n['published_at'])) : '');
                    $extUrl = !empty($n['url']) ? (string)$n['url'] : null;
                    $source = !empty($n['source']) ? (string)$n['source'] : '';
                    ?>
                    <div class="col-12 col-lg-6">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <div class="row g-0">
                                <div class="col-12 col-md-5">
                                    <?php if ($extUrl): ?>
                                        <a href="<?= e($extUrl) ?>" target="_blank" rel="noopener noreferrer" class="d-block h-100">
                                            <img src="<?= e($imgSrc) ?>" class="w-100 h-100 object-fit-cover" alt="<?= e((string)$n['title']) ?>">
                                        </a>
                                    <?php else: ?>
                                        <img src="<?= e($imgSrc) ?>" class="w-100 h-100 object-fit-cover" alt="<?= e((string)$n['title']) ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-7">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                            <span class="badge text-bg-primary"><i class="bi bi-newspaper me-1"></i> Noticia</span>
                                            <?php if ($source): ?><span class="badge text-bg-secondary"><?= e($source) ?></span><?php endif; ?>
                                            <?php if ($date !== ''): ?><span class="text-secondary small"><?= e($date) ?></span><?php endif; ?>
                                        </div>
                                        <h2 class="h5 fw-semibold mb-2"><?= e((string)$n['title']) ?></h2>
                                        <?php if (!empty($n['excerpt'])): ?>
                                            <p class="text-secondary mb-2"><?= e((string)$n['excerpt']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($extUrl): ?>
                                            <a href="<?= e($extUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                                Ver noticia completa <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php elseif (!empty($n['content'])): ?>
                                            <div class="text-body small"><?= nl2br(e((string)$n['content'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

