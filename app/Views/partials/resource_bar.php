<?php
/**
 * Barre de ressource (HP, énergie, nerve, XP...).
 *
 * @var string $label    Nom affiché (ex: "HP", "Énergie")
 * @var int    $current  Valeur actuelle
 * @var int    $max      Valeur max
 * @var string $color    Token couleur Tailwind ('hp', 'energy', 'nerve', 'xp', 'danger', 'warning', 'success', 'info'...)
 */

$pct = (int) round(($current / max(1, $max)) * 100);

// Map color -> classes (explicite pour la fiabilité du Tailwind JIT).
$colorClasses = match ($color) {
    'hp'      => ['border' => 'border-hp/40',      'bg' => 'bg-hp/10',      'text' => 'text-hp',      'fill' => 'bg-hp'],
    'energy'  => ['border' => 'border-energy/40',  'bg' => 'bg-energy/5',   'text' => 'text-energy',  'fill' => 'bg-energy'],
    'nerve'   => ['border' => 'border-nerve/40',   'bg' => 'bg-nerve/10',   'text' => 'text-nerve',   'fill' => 'bg-nerve'],
    'xp'      => ['border' => 'border-xp/40',      'bg' => 'bg-xp/10',      'text' => 'text-xp',      'fill' => 'bg-xp'],
    'danger'  => ['border' => 'border-danger/40',  'bg' => 'bg-danger/10',  'text' => 'text-danger',  'fill' => 'bg-danger'],
    'warning' => ['border' => 'border-warning/40', 'bg' => 'bg-warning/10', 'text' => 'text-warning', 'fill' => 'bg-warning'],
    'success' => ['border' => 'border-success/40', 'bg' => 'bg-success/10', 'text' => 'text-success', 'fill' => 'bg-success'],
    default   => ['border' => 'border-primary/40', 'bg' => 'bg-primary/5',  'text' => 'text-primary', 'fill' => 'bg-primary'],
};
?>
<div class="border <?= $colorClasses['border'] ?> bg-surface-alt rounded p-3">
    <div class="flex justify-between items-baseline">
        <span class="<?= $colorClasses['text'] ?> text-sm font-bold"><?= esc($label) ?></span>
        <span class="<?= $colorClasses['text'] ?> text-xs"><?= number_format($current) ?> / <?= number_format($max) ?></span>
    </div>
    <div class="h-2 bg-stone-200 mt-2 rounded overflow-hidden">
        <div class="h-full <?= $colorClasses['fill'] ?> transition-all" style="width: <?= $pct ?>%"></div>
    </div>
</div>
