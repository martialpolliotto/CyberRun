<?php
/**
 * Bouton cyberpunk.
 *
 * @var string $label
 * @var string|null $type        submit|button (def: submit)
 * @var string|null $variant     accent|primary|danger|ghost (def: accent)
 * @var string|null $extraClass
 */

$type    = $type    ?? 'submit';
$variant = $variant ?? 'accent';

$colors = match ($variant) {
    'primary' => 'bg-primary text-white border-primary hover:bg-slate-700',
    'danger'  => 'bg-danger text-white border-danger hover:bg-red-700',
    'ghost'   => 'bg-transparent text-primary border-line hover:border-accent hover:text-accent',
    default   => 'bg-accent text-white border-accent hover:bg-sky-800',
};
?>
<button type="<?= esc($type) ?>"
        class="w-full px-4 py-2 border font-medium rounded transition <?= $colors ?> <?= $extraClass ?? '' ?>">
    <?= esc($label) ?>
</button>
