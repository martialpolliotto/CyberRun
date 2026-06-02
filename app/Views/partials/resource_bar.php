<?php
/**
 * Barre de ressource (Life, Energy, Nerve, Xp, etc.). Bootstrap + couleurs Torn-style.
 *
 * @var string $label    Nom affiché
 * @var int    $current
 * @var int    $max
 * @var string $color    Une des clefs cr-bar-* : life, energy, nerve, xp, addiction, mission, hp.
 *                       Alias historiques : 'hp' -> 'life'.
 */

$pct = (int) round(($current / max(1, $max)) * 100);

// Normalise les alias historiques (avant le rename HP->Life).
$colorAliases = ['hp' => 'life'];
$colorKey = $colorAliases[$color] ?? $color;
?>
<div class="card mb-2">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="fw-bold small text-uppercase"><?= esc($label) ?></span>
            <span class="small text-muted font-monospace"><?= number_format($current) ?> / <?= number_format($max) ?></span>
        </div>
        <div class="progress cr-bar-notched" style="height: 8px;">
            <div class="progress-bar cr-bar-<?= esc($colorKey, 'attr') ?>" style="width: <?= $pct ?>%"></div>
        </div>
    </div>
</div>
