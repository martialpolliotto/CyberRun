<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
helper('number');
$categoryLabels = [
    'crime'   => 'Crime',
    'train'   => 'Entraînement',
    'combat'  => 'Combat',
    'level'   => 'Progression',
    'social'  => 'Social',
    'eco'     => 'Économie',
    'general' => 'Général',
];
?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-trophy"></i> Trophées</h1>
            <p class="text-muted small mb-0">
                <strong><?= (int) $counts['unlocked'] ?></strong> / <?= (int) $counts['total'] ?> débloqués.
                Les trophées débloquent automatiquement quand tu atteins les seuils.
            </p>
        </div>
    </div>

    <?php foreach ($grouped as $cat => $items): ?>
        <h2 class="small text-uppercase text-muted mb-2"><?= esc($categoryLabels[$cat] ?? $cat) ?></h2>
        <div class="row g-2 mb-3">
            <?php foreach ($items as $a):
                $unlocked = $a['unlocked_at'] !== null;
                $progress = (int) ($a['progress'] ?? 0);
                $count    = (int) $a['trigger_count'];
                $pct      = $count > 0 ? min(100, (int) round($progress / $count * 100)) : 0;
            ?>
                <div class="col-md-6">
                    <div class="card h-100 <?= $unlocked ? 'border-dark' : '' ?>" style="<?= $unlocked ? '' : 'opacity: 0.65;' ?>">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="d-flex align-items-center justify-content-center"
                                     style="width: 3rem; height: 3rem; border-radius: 50%; background: <?= $unlocked ? '#212529' : '#dee2e6' ?>; color: <?= $unlocked ? '#fff' : '#6c757d' ?>; flex-shrink: 0;">
                                    <i class="bi <?= esc($a['icon']) ?> fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= esc($a['name']) ?></div>
                                    <div class="small text-muted mb-1"><?= esc($a['description']) ?></div>
                                    <?php if (! $unlocked): ?>
                                        <div class="progress cr-bar-notched" style="height: 4px;">
                                            <div class="progress-bar bg-dark" style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mt-1">
                                            <span class="font-monospace"><?= number_format($progress) ?> / <?= number_format($count) ?></span>
                                            <span>+<?= number_format((int) $a['reward_credits']) ?>¢ +<?= (int) $a['reward_xp'] ?> XP</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-success">
                                            <i class="bi bi-check-circle-fill"></i> Débloqué le <?= esc(substr((string) $a['unlocked_at'], 0, 10)) ?>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>

    <?php if (empty($grouped)): ?>
        <p class="text-muted fst-italic small">Aucun trophée configuré pour l'instant.</p>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
