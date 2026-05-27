<?php
/**
 * Barre de ressource (HP, énergie, nerve, XP...). Bootstrap.
 *
 * @var string $label    Nom affiché
 * @var int    $current
 * @var int    $max
 * @var string $color    Conservé pour compat — toujours rendu en noir.
 */

$pct = (int) round(($current / max(1, $max)) * 100);
?>
<div class="card mb-2">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="fw-bold small text-uppercase"><?= esc($label) ?></span>
            <span class="small text-muted font-monospace"><?= number_format($current) ?> / <?= number_format($max) ?></span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-dark" style="width: <?= $pct ?>%"></div>
        </div>
    </div>
</div>
