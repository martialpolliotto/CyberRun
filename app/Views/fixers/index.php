<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-4">
        <h1 class="h3 mb-0">Fixers</h1>
        <p class="text-muted small mb-0">Les PNJ qui te donnent des missions et t'apprennent le quartier.</p>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if (empty($fixers)): ?>
        <p class="text-muted fst-italic small">Aucun fixer débloqué pour l'instant.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($fixers as $f): ?>
                <?php
                    $badge = match ($f['_status']) {
                        'new'         => '<span class="badge bg-dark">Nouvelle mission</span>',
                        'claimable'   => '<span class="badge bg-dark">À réclamer</span>',
                        'in_progress' => '<span class="badge bg-secondary">En cours</span>',
                        default       => '<span class="badge bg-light text-muted">Chaîne terminée</span>',
                    };
                ?>
                <div class="col-md-6">
                    <a href="/fixers/<?= esc($f['slug']) ?>" class="card text-decoration-none text-dark h-100">
                        <div class="card-body d-flex gap-3">
                            <?php if (! empty($f['image_path'])): ?>
                                <img src="<?= esc($f['image_path']) ?>" alt="" class="object-fit-cover bg-light border" style="width: 5rem; height: 5rem;">
                            <?php else: ?>
                                <div class="bg-light border d-flex align-items-center justify-content-center text-muted small" style="width: 5rem; height: 5rem;">
                                    portrait
                                </div>
                            <?php endif ?>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h2 class="h6 mb-1"><?= esc($f['name']) ?></h2>
                                    <?= $badge ?>
                                </div>
                                <?php if (! empty($f['tagline'])): ?>
                                    <p class="small fst-italic mb-0">« <?= esc($f['tagline']) ?> »</p>
                                <?php endif ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
