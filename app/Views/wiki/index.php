<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-book"></i> Wiki gameplay</h1>
            <p class="text-muted small mb-0">
                Toutes les mécaniques du jeu, mises à jour automatiquement quand la doc évolue.
            </p>
        </div>
    </div>

    <?php if (empty($sections)): ?>
        <p class="text-muted fst-italic small">Aucune section trouvée. Vérifier <code>docs/GAMEPLAY.md</code>.</p>
    <?php else: ?>
        <div class="card">
            <ul class="list-group list-group-flush">
                <?php foreach ($sections as $s): ?>
                    <li class="list-group-item">
                        <a href="/wiki/<?= esc($s['slug'], 'attr') ?>" class="text-dark text-decoration-none d-flex gap-2 align-items-baseline">
                            <span class="text-muted font-monospace small" style="width: 2rem;"><?= esc($s['number']) ?>.</span>
                            <span class="fw-semibold"><?= esc($s['title']) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
