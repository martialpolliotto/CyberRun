<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $hpPct     = (int) round(($player['hp_current']     / max(1, $player['hp_max']))     * 100);
    $energyPct = (int) round(($player['energy_current'] / max(1, $player['energy_max'])) * 100);
    $nervePct  = (int) round(($player['nerve_current']  / max(1, $player['nerve_max']))  * 100);
    $xpPct     = (int) round(($player['xp']             / max(1, $xpToNext))             * 100);

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
    <div class="border border-neon-cyan/40 bg-neon-cyan/5 p-4">
        <p class="text-xs text-neon-cyan/60 mb-1">&gt; PROFIL_NETRUNNER</p>
        <h1 class="text-3xl md:text-4xl font-bold text-neon-pink">
            <?= esc($user->username) ?>
        </h1>
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
            <span class="text-neon-cyan">Niveau <span class="text-white font-bold"><?= (int) $player['level'] ?></span></span>
            <span class="text-neon-cyan/60">XP <?= number_format($player['xp']) ?> / <?= number_format($xpToNext) ?></span>
            <?php if (! empty($player['in_hospital_until'])): ?>
                <span class="text-red-400">[ EN CYBERCLINIQUE ]</span>
            <?php endif; ?>
        </div>
        <div class="h-1 bg-black/40 mt-2">
            <div class="h-full bg-gradient-to-r from-neon-cyan to-neon-pink" style="width: <?= $xpPct ?>%"></div>
        </div>
    </div>

    <!-- Ressources -->
    <div class="grid md:grid-cols-3 gap-3">
        <!-- HP -->
        <div class="border border-red-500/40 bg-red-900/10 p-3">
            <div class="flex justify-between items-baseline">
                <span class="text-red-400 text-sm font-bold">HP</span>
                <span class="text-red-300 text-xs"><?= (int) $player['hp_current'] ?> / <?= (int) $player['hp_max'] ?></span>
            </div>
            <div class="h-2 bg-black/50 mt-2 border border-red-500/30">
                <div class="h-full bg-gradient-to-r from-red-700 via-red-500 to-red-300" style="width: <?= $hpPct ?>%"></div>
            </div>
        </div>
        <!-- Énergie -->
        <div class="border border-neon-cyan/40 bg-neon-cyan/5 p-3">
            <div class="flex justify-between items-baseline">
                <span class="text-neon-cyan text-sm font-bold">Énergie</span>
                <span class="text-neon-cyan text-xs"><?= (int) $player['energy_current'] ?> / <?= (int) $player['energy_max'] ?></span>
            </div>
            <div class="h-2 bg-black/50 mt-2 border border-neon-cyan/30">
                <div class="h-full bg-gradient-to-r from-cyan-700 via-cyan-400 to-neon-cyan" style="width: <?= $energyPct ?>%"></div>
            </div>
        </div>
        <!-- Nerve -->
        <div class="border border-neon-yellow/40 bg-neon-yellow/5 p-3">
            <div class="flex justify-between items-baseline">
                <span class="text-neon-yellow text-sm font-bold">Nerve</span>
                <span class="text-neon-yellow text-xs"><?= (int) $player['nerve_current'] ?> / <?= (int) $player['nerve_max'] ?></span>
            </div>
            <div class="h-2 bg-black/50 mt-2 border border-neon-yellow/30">
                <div class="h-full bg-gradient-to-r from-yellow-700 via-yellow-400 to-neon-yellow" style="width: <?= $nervePct ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div>
        <p class="text-xs text-neon-cyan/60 mb-2">&gt; STATS_COMBAT</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($stats as $label => $value): ?>
                <div class="border border-neon-cyan/30 bg-black/40 p-3 text-center hover:border-neon-pink/60 transition">
                    <p class="text-neon-cyan/70 text-xs uppercase tracking-wider"><?= esc($label) ?></p>
                    <p class="text-3xl text-white font-bold mt-1"><?= number_format($value) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Crédits -->
    <div class="border border-neon-yellow/40 bg-neon-yellow/5 p-3 flex justify-between items-center">
        <span class="text-xs text-neon-yellow/60">&gt; SOLDE</span>
        <span class="text-2xl text-neon-yellow font-bold">¢<?= number_format($player['credits']) ?></span>
    </div>

</div>

<?= $this->endSection() ?>
