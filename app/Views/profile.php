<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $xpPct = (int) round(($player['xp'] / max(1, $xpToNext)) * 100);

    $stats = [
        'Force'    => $player['stat_force'],
        'Blindage' => $player['stat_blindage'],
        'Réflexes' => $player['stat_reflexes'],
        'Hack'     => $player['stat_hack'],
    ];
?>

<?= $this->section('content') ?>

<div class="max-w-4xl mx-auto space-y-4">

    <!-- Identité -->
    <?php
        $identitySlot = '<h1 class="text-3xl md:text-4xl font-bold text-accent">' . esc($user->username) . '</h1>'
            . '<div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">'
            .   '<span class="text-primary">Niveau <span class="text-white font-bold">' . (int) $player['level'] . '</span></span>'
            .   '<span class="text-primary/60">XP ' . number_format($player['xp']) . ' / ' . number_format($xpToNext) . '</span>'
            .   (! empty($player['in_hospital_until'])
                    ? '<span class="text-danger">[ EN CYBERCLINIQUE ]</span>'
                    : '')
            . '</div>'
            . '<div class="h-1 bg-black/40 mt-2">'
            .   '<div class="h-full bg-xp" style="width: ' . $xpPct . '%"></div>'
            . '</div>';
    ?>
    <?= view('partials/bloc', [
        'title'   => 'PROFIL_NETRUNNER',
        'variant' => 'primary',
        'slot'    => $identitySlot,
    ]) ?>

    <!-- Ressources -->
    <div class="grid md:grid-cols-3 gap-3">
        <?= view('partials/resource_bar', ['label' => 'HP',      'current' => $player['hp_current'],     'max' => $player['hp_max'],     'color' => 'hp']) ?>
        <?= view('partials/resource_bar', ['label' => 'Énergie', 'current' => $player['energy_current'], 'max' => $player['energy_max'], 'color' => 'energy']) ?>
        <?= view('partials/resource_bar', ['label' => 'Nerve',   'current' => $player['nerve_current'],  'max' => $player['nerve_max'],  'color' => 'nerve']) ?>
    </div>

    <!-- Stats -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; STATS_COMBAT</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($stats as $label => $value): ?>
                <?= view('partials/stat_card', ['label' => $label, 'value' => $value]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Crédits -->
    <?= view('partials/bloc', [
        'title'      => 'SOLDE',
        'variant'    => 'warning',
        'extraClass' => 'flex justify-between items-center',
        'slot'       => '<span class="text-2xl text-credits font-bold">¢' . number_format($player['credits']) . '</span>',
    ]) ?>

</div>

<?= $this->endSection() ?>
