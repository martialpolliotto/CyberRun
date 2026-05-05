<?php
/**
 * Alerte (erreur, succès, warning, info).
 *
 * @var string|array $message
 * @var string|null  $variant   danger|success|warning|info (def: info)
 */

$variant = $variant ?? 'info';
$styles = match ($variant) {
    'danger'  => ['border' => 'border-danger',  'bg' => 'bg-danger/10',  'text' => 'text-danger',  'prefix' => '! ERREUR'],
    'success' => ['border' => 'border-success', 'bg' => 'bg-success/10', 'text' => 'text-success', 'prefix' => '> OK'],
    'warning' => ['border' => 'border-warning', 'bg' => 'bg-warning/10', 'text' => 'text-warning', 'prefix' => '~ ALERTE'],
    default   => ['border' => 'border-primary', 'bg' => 'bg-primary/10', 'text' => 'text-primary', 'prefix' => '> INFO'],
};
?>
<div class="border <?= $styles['border'] ?> <?= $styles['bg'] ?> <?= $styles['text'] ?> p-3 text-sm">
    <p class="text-xs opacity-70 mb-1 uppercase tracking-wider"><?= $styles['prefix'] ?></p>
    <?php if (is_array($message)): ?>
        <ul class="list-none space-y-1">
            <?php foreach ($message as $m): ?>
                <li>&gt; <?= esc((string) $m) ?></li>
            <?php endforeach ?>
        </ul>
    <?php else: ?>
        <p>&gt; <?= esc((string) $message) ?></p>
    <?php endif ?>
</div>
