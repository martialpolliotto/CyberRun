<?= $this->extend('layouts/main') ?>

<?php
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

    <?= view('lab/_content', [
        'player'       => $player,
        'cost'         => $cost,
        'gain'         => $gain,
        'statLabels'   => $statLabels,
        'statColumns'  => $statColumns,
        'canTrain'     => $canTrain,
        'flash_variant'=> session()->has('message') ? 'success' : (session()->has('error') ? 'danger' : null),
        'flash_message'=> session('message') ?? session('error') ?? null,
    ]) ?>

    <p class="small text-muted text-center mt-4">
        L'énergie regen automatiquement (cron toutes les minutes).
    </p>

</div>

<?= $this->endSection() ?>
