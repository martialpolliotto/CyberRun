<?php
/**
 * Carte de statistique (Force, Blindage, Réflexes, Hack...). Bootstrap.
 *
 * @var string  $label
 * @var int     $value
 * @var string|null $href  Si fourni, la carte devient cliquable.
 */

$inner = '<div class="small text-muted text-uppercase">' . esc($label) . '</div>'
       . '<div class="fs-3 fw-bold mt-1">' . number_format($value) . '</div>';
?>
<?php if (! empty($href)): ?>
    <a href="<?= esc($href) ?>" class="card text-center text-decoration-none text-dark">
        <div class="card-body p-3"><?= $inner ?></div>
    </a>
<?php else: ?>
    <div class="card text-center">
        <div class="card-body p-3"><?= $inner ?></div>
    </div>
<?php endif ?>
