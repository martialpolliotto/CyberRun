<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $statLabels = [
        'force'    => 'Force',
        'blindage' => 'Blindage',
        'reflexes' => 'Réflexes',
        'hack'     => 'Hack',
    ];
    $statColumns = [
        'force'    => 'stat_force',
        'blindage' => 'stat_blindage',
        'reflexes' => 'stat_reflexes',
        'hack'     => 'stat_hack',
    ];

    $cost     = $trainEnergyCost;
    $gain     = $trainStatGain;
    $canTrain = (int) $player['energy_current'] >= $cost
        && (empty($player['in_hospital_until'])
            || \CodeIgniter\I18n\Time::parse($player['in_hospital_until'])->isBefore(\CodeIgniter\I18n\Time::now()));
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Le Lab</h1>
            <p class="text-muted small mb-0">Coût : <?= $cost ?> énergie par entraînement.</p>
        </div>
        <div class="text-end small">
            <div class="text-muted text-uppercase">Pseudo</div>
            <div class="fw-bold"><?= esc($user->username) ?></div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?= view('partials/resource_bar', [
        'label'   => 'Énergie disponible',
        'current' => $player['energy_current'],
        'max'     => $player['energy_max'],
        'color'   => 'energy',
    ]) ?>

    <div class="row g-3 mt-1">
        <?php foreach ($statLabels as $slug => $label): ?>
            <?php $value = (int) $player[$statColumns[$slug]]; ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="small text-muted text-uppercase"><?= esc($label) ?></div>
                                <div class="fs-3 fw-bold"><?= number_format($value) ?></div>
                            </div>
                            <div class="small text-muted">+<?= $gain ?> par session</div>
                        </div>
                        <form method="post" action="/lab/train/<?= esc($slug) ?>">
                            <?= csrf_field() ?>
                            <button type="submit"
                                    <?= $canTrain ? '' : 'disabled' ?>
                                    class="btn btn-dark w-100">
                                <?php if ($canTrain): ?>
                                    Entraîner (-<?= $cost ?> NRG)
                                <?php else: ?>
                                    <?= empty($player['in_hospital_until']) ? 'Énergie insuffisante' : 'En cyberclinique' ?>
                                <?php endif ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <p class="small text-muted text-center mt-4">
        L'énergie regen automatiquement (cron toutes les minutes).
    </p>

</div>

<?= $this->endSection() ?>
