<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    // Slug URL → libellé affiché + colonne BDD (miroir de PlayerModel::TRAINABLE_STATS).
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

<div class="max-w-4xl mx-auto space-y-4">

    <!-- En-tete Lab -->
    <div class="flex items-end justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-accent">&gt; LE_LAB</h1>
            <p class="text-primary/60 text-sm mt-1">// Forge ton chrome. Coût : <?= $cost ?> énergie par entraînement.</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-primary/60 uppercase tracking-wider">Pseudo</p>
            <p class="text-accent font-bold"><?= esc($user->username) ?></p>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Barre energie en evidence -->
    <?= view('partials/resource_bar', [
        'label'   => 'Énergie disponible',
        'current' => $player['energy_current'],
        'max'     => $player['energy_max'],
        'color'   => 'energy',
    ]) ?>

    <!-- 4 cartes d'entrainement -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($statLabels as $slug => $label): ?>
            <?php $value = (int) $player[$statColumns[$slug]]; ?>
            <div class="border border-primary/30 bg-black/40 p-4 hover:border-accent/60 transition">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-primary/70 text-xs uppercase tracking-wider"><?= esc($label) ?></p>
                        <p class="text-3xl text-white font-bold"><?= number_format($value) ?></p>
                    </div>
                    <p class="text-success text-xs">+<?= $gain ?> par session</p>
                </div>

                <form method="post" action="/lab/train/<?= esc($slug) ?>">
                    <?= csrf_field() ?>
                    <button type="submit"
                            <?= $canTrain ? '' : 'disabled' ?>
                            class="w-full px-3 py-2 border font-bold uppercase tracking-wider text-sm transition <?php
                                echo $canTrain
                                    ? 'bg-accent text-white border-accent hover:bg-pink-600 cursor-pointer'
                                    : 'bg-black/30 text-primary/30 border-primary/20 cursor-not-allowed';
                            ?>">
                        <?php if ($canTrain): ?>
                            Entraîner (-<?= $cost ?> NRG)
                        <?php else: ?>
                            <?= empty($player['in_hospital_until']) ? 'Énergie insuffisante' : 'En cyberclinique' ?>
                        <?php endif ?>
                    </button>
                </form>
            </div>
        <?php endforeach ?>
    </div>

    <p class="text-xs text-primary/40 text-center mt-6">
        Astuce : l'énergie regen automatiquement (à venir : cron toutes les 5 min).
    </p>

</div>

<?= $this->endSection() ?>
