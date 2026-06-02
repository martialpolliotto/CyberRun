<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $xpPct = (int) round(($player['xp'] / max(1, $xpToNext)) * 100);

    $statLabels = [
        'force'    => 'Force',
        'blindage' => 'Blindage',
        'reflexes' => 'Réflexes',
        'hack'     => 'Hack',
    ];
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <!-- Identité -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Profil</span>
            <a href="/profile/edit" class="text-decoration-none text-muted small">Personnaliser</a>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-2">
                <?php if (! empty($player['avatar_path'])): ?>
                    <img src="<?= esc($player['avatar_path']) ?>" alt="avatar"
                         class="rounded border" style="width: 72px; height: 72px; object-fit: cover; flex-shrink: 0;">
                <?php endif ?>
                <div class="flex-grow-1">
                    <h1 class="h3 mb-1"><?= esc($user->username) ?></h1>
                    <?php if (! empty($player['signature'])): ?>
                        <p class="text-muted fst-italic small mb-1"><?= esc($player['signature']) ?></p>
                    <?php endif ?>
                    <div class="d-flex flex-wrap gap-3 small mb-2">
                        <span>Niveau <strong><?= (int) $player['level'] ?></strong></span>
                        <span class="text-muted">XP <?= number_format($player['xp']) ?> / <?= number_format($xpToNext) ?></span>
                        <?php if (! empty($player['in_hospital_until'])): ?>
                            <span class="fw-bold">[ EN CYBERCLINIQUE ]</span>
                        <?php endif ?>
                    </div>
                    <div class="progress cr-bar-notched" style="height: 6px;">
                        <div class="progress-bar cr-bar-xp" style="width: <?= $xpPct ?>%"></div>
                    </div>
                </div>
            </div>
            <?php if (! empty($player['bio'])): ?>
                <hr class="my-3">
                <div class="small" style="white-space: pre-wrap; word-wrap: break-word;"><?= esc($player['bio']) ?></div>
            <?php endif ?>
        </div>
    </div>

    <!-- Ressources -->
    <div class="row g-3 mb-3">
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Life',    'current' => $player['hp_current'],     'max' => $player['hp_max'],     'color' => 'hp']) ?></div>
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Énergie', 'current' => $player['energy_current'], 'max' => $player['energy_max'], 'color' => 'energy']) ?></div>
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Nerve',   'current' => $player['nerve_current'],  'max' => $player['nerve_max'],  'color' => 'nerve']) ?></div>
    </div>

    <!-- Stats (base + bonus = total) -->
    <h2 class="small text-uppercase text-muted mb-2">Stats de combat</h2>
    <div class="row g-3 mb-3">
        <?php foreach ($statLabels as $key => $label): ?>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body p-3">
                        <div class="small text-muted text-uppercase"><?= esc($label) ?></div>
                        <div class="fs-3 fw-bold mt-1"><?= number_format($stats['total'][$key]) ?></div>
                        <?php if ($stats['bonus'][$key] > 0): ?>
                            <div class="small text-muted mt-1">
                                <?= $stats['base'][$key] ?> + <?= $stats['bonus'][$key] ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Crédits -->
    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">Solde</span>
            <span class="fs-4 fw-bold">¢<?= number_format($player['credits']) ?></span>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
