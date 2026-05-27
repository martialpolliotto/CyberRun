<?php
/**
 * Alerte (Bootstrap).
 *
 * @var string|array $message
 * @var string|null  $variant   danger|success|warning|info (def: info)
 */

$variant = $variant ?? 'info';
$class   = match ($variant) {
    'danger'  => 'alert-dark',
    'success' => 'alert-secondary',
    'warning' => 'alert-secondary',
    default   => 'alert-light',
};
?>
<div class="alert <?= $class ?>" role="alert">
    <?php if (is_array($message)): ?>
        <ul class="mb-0 ps-3">
            <?php foreach ($message as $m): ?>
                <li><?= nl2br(esc((string) $m)) ?></li>
            <?php endforeach ?>
        </ul>
    <?php else: ?>
        <?= nl2br(esc((string) $message)) ?>
    <?php endif ?>
</div>
