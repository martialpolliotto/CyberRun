<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-4">
        <h1 class="h3 mb-0">Marchés</h1>
        <p class="text-muted small mb-0">Visite les commerces du quartier pour t'équiper.</p>
    </div>

    <div class="row g-3">
        <?php foreach ($vendors as $v): ?>
            <div class="col-md-4">
                <a href="/shop/<?= esc($v['slug']) ?>" class="card text-decoration-none text-dark h-100">
                    <?php if (! empty($v['image_path'])): ?>
                        <img src="<?= esc($v['image_path']) ?>" alt="<?= esc($v['name']) ?>"
                             class="card-img-top object-fit-cover bg-light" style="height: 10rem;">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center text-muted small text-uppercase" style="height: 10rem;">
                            portrait à venir
                        </div>
                    <?php endif ?>
                    <div class="card-body">
                        <h2 class="h5 mb-1"><?= esc($v['name']) ?></h2>
                        <?php if (! empty($v['tagline'])): ?>
                            <p class="small fst-italic mb-1">« <?= esc($v['tagline']) ?> »</p>
                        <?php endif ?>
                        <?php if (! empty($v['description'])): ?>
                            <p class="text-muted small mb-0"><?= esc($v['description']) ?></p>
                        <?php endif ?>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

</div>

<?= $this->endSection() ?>
