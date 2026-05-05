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
    'primary' => 'bg-primary text-black border-primary hover:bg-cyan-300',
    'danger'  => 'bg-danger text-white border-danger hover:bg-red-700',
    'ghost'   => 'bg-transparent text-primary border-primary/40 hover:border-accent hover:text-accent',
    default   => 'bg-accent text-white border-accent hover:bg-pink-600',
};
?>
<button type="<?= esc($type) ?>"
        class="w-full px-4 py-2 border font-bold uppercase tracking-wider transition <?= $colors ?> <?= $extraClass ?? '' ?>">
    <?= esc($label) ?>
</button>
