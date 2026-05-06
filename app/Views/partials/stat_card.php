<?php
/**
 * Carte de statistique (Force, Blindage, Réflexes, Hack...).
 *
 * @var string  $label
 * @var int     $value
 * @var string|null $href  Si fourni, la carte devient cliquable (futur lien vers /lab/train/X par ex).
 */

$inner = '<p class="text-primary/70 text-xs uppercase tracking-wider">' . esc($label) . '</p>'
       . '<p class="text-3xl text-primary font-bold mt-1">' . number_format($value) . '</p>';
?>
<?php if (! empty($href)): ?>
    <a href="<?= esc($href) ?>" class="block border border-primary/30 bg-surface-alt p-3 text-center hover:border-accent/60 hover:bg-primary/5 transition">
        <?= $inner ?>
    </a>
<?php else: ?>
    <div class="border border-primary/30 bg-surface-alt p-3 text-center hover:border-accent/60 transition">
        <?= $inner ?>
    </div>
<?php endif; ?>
