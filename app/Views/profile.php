<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $xpPct = (int) round(($player['xp'] / max(1, $xpToNext)) * 100);

    // $stats vient du controller : ['base' => [...], 'bonus' => [...], 'total' => [...]]
    $statLabels = [
        'force'    => 'Force',
        'blindage' => 'Blindage',
        'reflexes' => 'Réflexes',
        'hack'     => 'Hack',
    ];
?>

<?= $this->section('content') ?>

<div class="max-w-4xl mx-auto space-y-4">

    <!-- Identité -->
    <?php
        $identitySlot = '<h1 class="text-3xl md:text-4xl font-bold text-accent">' . esc($user->username) . '</h1>'
            . '<div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">'
            .   '<span class="text-primary">Niveau <span class="text-primary font-bold">' . (int) $player['level'] . '</span></span>'
            .   '<span class="text-primary/60">XP ' . number_format($player['xp']) . ' / ' . number_format($xpToNext) . '</span>'
            .   (! empty($player['in_hospital_until'])
                    ? '<span class="text-danger">[ EN CYBERCLINIQUE ]</span>'
                    : '')
            . '</div>'
            . '<div class="h-1 bg-surface-alt mt-2">'
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

    <!-- Stats (base + bonus equipement = total) -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; STATS_COMBAT</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($statLabels as $key => $label): ?>
                <div class="border border-primary/30 bg-surface-alt p-3 text-center hover:border-accent/60 transition">
                    <p class="text-primary/70 text-xs uppercase tracking-wider"><?= esc($label) ?></p>
                    <p class="text-3xl text-primary font-bold mt-1"><?= number_format($stats['total'][$key]) ?></p>
                    <?php if ($stats['bonus'][$key] > 0): ?>
                        <p class="text-xs text-primary/60 mt-1">
                            <?= $stats['base'][$key] ?>
                            <span class="text-success">+ <?= $stats['bonus'][$key] ?></span>
                        </p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
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
