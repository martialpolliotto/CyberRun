<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $statLabels = ['force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack'];
    $statLabel = isset($category['primary_stat']) && isset($statLabels[$category['primary_stat']]) ? $statLabels[$category['primary_stat']] : null;
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="small mb-3">
        <a href="/crimes" class="text-muted text-decoration-none">← Toutes les catégories</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h1 class="h3 mb-1"><?= esc($category['name']) ?></h1>
                    <?php if (! empty($category['description'])): ?>
                        <p class="small mb-0"><?= esc($category['description']) ?></p>
                    <?php endif ?>
                </div>
                <div class="text-end">
                    <div class="small text-muted text-uppercase">XP spécialisation</div>
                    <div class="fs-4 fw-bold"><?= number_format((int) $progress['xp']) ?></div>
                    <?php if ($statLabel !== null): ?>
                        <div class="small text-muted">Stat clé : <?= esc($statLabel) ?></div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <?php
        // Au premier render on prend le flash de la session. Les rechargements HTMX
        // recevront leur propre flash_variant/flash_message depuis le controller.
        $initialFlashVariant = null;
        $initialFlashMessage = null;
        if (session()->has('message')) {
            $initialFlashVariant = 'success';
            $initialFlashMessage = session('message');
        } elseif (session()->has('error')) {
            $initialFlashVariant = 'danger';
            $initialFlashMessage = session('error');
        }
    ?>
    <?= view('crimes/_list', [
        'player'        => $player,
        'crimes'        => $crimes,
        'flash_variant' => $initialFlashVariant,
        'flash_message' => $initialFlashMessage,
    ]) ?>

</div>

<?= $this->endSection() ?>
