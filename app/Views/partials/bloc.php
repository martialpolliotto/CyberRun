<?php
/**
 * Bloc encadré (carte sobre Torn-like).
 *
 * @var string|null $title    Label affiché en en-tête.
 * @var string      $variant  primary|accent|danger|warning|success — détermine la couleur du cadre + label.
 * @var string|null $extraClass  Classes Tailwind supplémentaires (ex: padding, layout).
 * @var string|null $slot     Contenu HTML du bloc.
 */

$variant = $variant ?? 'primary';

// Sober Torn-like : carte blanche (bg-surface-alt) avec une bordure et un label coloré selon variant.
$styles = match ($variant) {
    'accent'  => ['border' => 'border-accent/30',  'label' => 'text-accent'],
    'danger'  => ['border' => 'border-danger/30',  'label' => 'text-danger'],
    'warning' => ['border' => 'border-warning/40', 'label' => 'text-warning'],
    'success' => ['border' => 'border-success/40', 'label' => 'text-success'],
    default   => ['border' => 'border-line',       'label' => 'text-muted'],
};
?>
<div class="border <?= $styles['border'] ?> bg-surface-alt rounded p-4 <?= $extraClass ?? '' ?>">
    <?php if (! empty($title)): ?>
        <p class="text-xs <?= $styles['label'] ?> mb-2 uppercase tracking-wider font-semibold"><?= esc($title) ?></p>
    <?php endif; ?>
    <?= $slot ?? '' ?>
</div>
