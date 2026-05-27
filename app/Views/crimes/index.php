<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-4">
        <h1 class="h3 mb-0">Crimes</h1>
        <p class="text-muted small mb-0">Catégories d'activités illégales. Chaque catégorie a sa propre XP de spécialisation qui débloque les crimes plus juteux.</p>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if (empty($categories)): ?>
        <p class="text-muted fst-italic small">Aucune catégorie configurée.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <?php
                    $catXp = (int) ($progressMap[$cat['id']]['xp'] ?? 0);
                    $statLabels = ['force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack'];
                    $statLabel = isset($cat['primary_stat']) && isset($statLabels[$cat['primary_stat']]) ? $statLabels[$cat['primary_stat']] : null;
                ?>
                <div class="col-md-6">
                    <a href="/crimes/<?= esc($cat['slug']) ?>" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h2 class="h6 mb-0"><?= esc($cat['name']) ?></h2>
                                <span class="badge bg-dark"><?= number_format($catXp) ?> XP</span>
                            </div>
                            <?php if ($statLabel !== null): ?>
                                <div class="small text-muted mb-1">Stat dominante : <strong><?= esc($statLabel) ?></strong></div>
                            <?php else: ?>
                                <div class="small text-muted mb-1">Stat dominante : aucune</div>
                            <?php endif ?>
                            <?php if (! empty($cat['description'])): ?>
                                <p class="small mb-0"><?= esc($cat['description']) ?></p>
                            <?php endif ?>
                        </div>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
