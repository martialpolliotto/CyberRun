<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <h1 class="h3 mb-3">Marchands</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>

    <div class="row g-3">
        <?php foreach ($vendors as $v): ?>
            <div class="col-md-4">
                <a href="/admin/vendors/<?= (int) $v['id'] ?>/edit" class="card text-decoration-none text-dark h-100">
                    <?php if (! empty($v['image_path'])): ?>
                        <img src="<?= esc($v['image_path']) ?>" alt="" class="card-img-top object-fit-cover bg-light" style="height: 8rem;">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center text-muted small" style="height: 8rem;">
                            pas d'image
                        </div>
                    <?php endif ?>
                    <div class="card-body">
                        <div class="fw-bold"><?= esc($v['name']) ?></div>
                        <div class="text-muted small"><?= esc($v['slug']) ?></div>
                        <div class="small fst-italic mt-2">« <?= esc($v['tagline'] ?? '—') ?> »</div>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

</div>

<?= $this->endSection() ?>
