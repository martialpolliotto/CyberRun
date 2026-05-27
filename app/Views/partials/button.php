<?php
/**
 * Bouton.
 *
 * @var string $label
 * @var string|null $type        submit|button (def: submit)
 * @var string|null $variant     primary|secondary|danger|ghost (def: primary)
 * @var string|null $extraClass
 */

$type    = $type    ?? 'submit';
$variant = $variant ?? 'primary';

$btnClass = match ($variant) {
    'secondary' => 'btn-outline-dark',
    'danger'    => 'btn-dark',
    'ghost'     => 'btn-outline-secondary',
    default     => 'btn-dark',
};
?>
<button type="<?= esc($type) ?>"
        class="btn <?= $btnClass ?> w-100 <?= $extraClass ?? '' ?>">
    <?= esc($label) ?>
</button>
