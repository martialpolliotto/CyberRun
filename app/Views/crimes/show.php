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

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if (empty($crimes)): ?>
        <p class="text-muted fst-italic small">Aucun crime configuré dans cette catégorie.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($crimes as $c): ?>
                <div class="card <?= $c['_unlocked'] ? '' : 'text-muted' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="flex-grow-1" style="min-width: 16rem;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h2 class="h6 mb-0"><?= esc($c['name']) ?></h2>
                                    <?php if (! $c['_unlocked']): ?>
                                        <span class="badge bg-secondary">verrouillé · <?= (int) $c['min_category_xp'] ?> XP</span>
                                    <?php endif ?>
                                    <?php if ($c['_time_bonus_on']): ?>
                                        <span class="badge bg-dark">+<?= (int) $c['time_bonus_pct'] ?>% bonus horaire</span>
                                    <?php endif ?>
                                </div>
                                <?php if (! empty($c['description'])): ?>
                                    <p class="small mb-2"><?= esc($c['description']) ?></p>
                                <?php endif ?>
                                <div class="d-flex flex-wrap gap-3 small">
                                    <span><span class="text-muted text-uppercase">Nerve</span> <strong><?= (int) $c['nerve_cost'] ?></strong></span>
                                    <span><span class="text-muted text-uppercase">Réussite</span> <strong><?= (int) $c['_success_pct'] ?>%</strong></span>
                                    <span><span class="text-muted text-uppercase">Échec critique</span> <strong><?= (int) $c['critical_fail_pct'] ?>%</strong></span>
                                    <span><span class="text-muted text-uppercase">Gain</span> <strong>¢<?= number_format((int) $c['reward_credits_min']) ?>–<?= number_format((int) $c['reward_credits_max']) ?></strong></span>
                                    <span><span class="text-muted text-uppercase">XP</span> <strong>+<?= (int) $c['reward_xp'] ?> joueur / +<?= (int) $c['reward_category_xp'] ?> cat.</strong></span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Échec critique → <?= $c['critical_destination'] === 'hospital' ? 'cyberclinique' : 'prison' ?>
                                    (<?= (int) $c['critical_minutes_min'] ?>–<?= (int) $c['critical_minutes_max'] ?> min).
                                </div>
                            </div>
                            <div>
                                <?php if ($c['_unlocked']): ?>
                                    <form method="post" action="/crimes/attempt/<?= (int) $c['id'] ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                <?= (int) $player['nerve_current'] < (int) $c['nerve_cost'] ? 'disabled' : '' ?>
                                                class="btn btn-dark">
                                            <?= (int) $player['nerve_current'] < (int) $c['nerve_cost'] ? 'Nerve insuffisante' : 'Tenter (-' . (int) $c['nerve_cost'] . ' NRV)' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" disabled>Verrouillé</button>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
