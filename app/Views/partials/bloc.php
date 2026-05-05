<?php
/**
 * Bloc encadré "system readout" cyberpunk.
 *
 * @var string|null $title    Label affiché en en-tête (préfixé "> ").
 * @var string      $variant  primary|accent|danger|warning|success — détermine la couleur du cadre.
 * @var string|null $extraClass  Classes Tailwind supplémentaires (ex: padding, layout).
 * @var string|null $slot     Contenu HTML du bloc (ou utilisez la section "bloc" si extension).
 */

$variant = $variant ?? 'primary';

$styles = match ($variant) {
    'accent'  => ['border' => 'border-accent/40',  'bg' => 'bg-accent/5',  'label' => 'text-accent/70'],
    'danger'  => ['border' => 'border-danger/40',  'bg' => 'bg-danger/10', 'label' => 'text-danger/80'],
    'warning' => ['border' => 'border-warning/40', 'bg' => 'bg-warning/10','label' => 'text-warning/80'],
    'success' => ['border' => 'border-success/40', 'bg' => 'bg-success/10','label' => 'text-success/80'],
    default   => ['border' => 'border-primary/40', 'bg' => 'bg-primary/5', 'label' => 'text-primary/60'],
};
?>
<div class="border <?= $styles['border'] ?> <?= $styles['bg'] ?> p-3 <?= $extraClass ?? '' ?>">
    <?php if (! empty($title)): ?>
        <p class="text-xs <?= $styles['label'] ?> mb-1 uppercase tracking-wider">&gt; <?= esc($title) ?></p>
    <?php endif; ?>
    <?= $slot ?? '' ?>
</div>
