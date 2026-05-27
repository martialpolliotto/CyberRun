<?php
/**
 * Affiche le media d'un item : 3D si présent (priorité), sinon image, sinon placeholder.
 *
 * @var array       $item  Doit contenir image_path et/ou model_path
 * @var string|null $size  'sm' (96px), 'md' (192px, défaut), 'lg' (384px)
 */

$size      = $size ?? 'md';
$pixelSize = match ($size) {
    'sm'    => 96,
    'lg'    => 384,
    default => 192,
};
$styleDim  = 'width:' . $pixelSize . 'px; height:' . $pixelSize . 'px;';
$hasModel  = ! empty($item['model_path']);
$hasImage  = ! empty($item['image_path']);
$viewerId  = 'viewer-' . ($item['id'] ?? bin2hex(random_bytes(3)));
?>

<?php if ($hasModel): ?>
    <div id="<?= $viewerId ?>" class="border bg-dark" style="<?= $styleDim ?>"
         data-model-src="<?= esc($item['model_path']) ?>"
         data-viewer="three"></div>
    <?= $this->include('partials/item_viewer_three_init') ?>
<?php elseif ($hasImage): ?>
    <img src="<?= esc($item['image_path']) ?>" alt="<?= esc($item['name'] ?? '') ?>"
         class="border bg-light object-fit-contain" style="<?= $styleDim ?>">
<?php else: ?>
    <div class="border bg-light d-flex align-items-center justify-content-center text-muted small text-uppercase" style="<?= $styleDim ?>">
        no media
    </div>
<?php endif ?>
