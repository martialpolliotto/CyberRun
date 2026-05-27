<?php
/**
 * Carte (Bootstrap card).
 *
 * @var string|null $title    Label affiché en en-tête.
 * @var string      $variant  primary|accent|danger|warning|success — conservé pour compat, ignoré visuellement.
 * @var string|null $extraClass  Classes Bootstrap supplémentaires.
 * @var string|null $slot     Contenu HTML du bloc.
 */
?>
<div class="card mb-3 <?= $extraClass ?? '' ?>">
    <?php if (! empty($title)): ?>
        <div class="card-header bg-light small text-uppercase fw-semibold"><?= esc($title) ?></div>
    <?php endif; ?>
    <div class="card-body">
        <?= $slot ?? '' ?>
    </div>
</div>
