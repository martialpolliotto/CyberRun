<?php
/**
 * Affiche le media d'un item : 3D si présent (priorité), sinon image, sinon placeholder.
 *
 * @var array       $item  Doit contenir image_path et/ou model_path
 * @var string|null $size  'sm' (96px), 'md' (192px, défaut), 'lg' (384px)
 */

$size      = $size ?? 'md';
$dimClass  = match ($size) {
    'sm'    => 'w-24 h-24',
    'lg'    => 'w-96 h-96',
    default => 'w-48 h-48',
};
$pixelSize = match ($size) {
    'sm'    => 96,
    'lg'    => 384,
    default => 192,
};
$hasModel  = ! empty($item['model_path']);
$hasImage  = ! empty($item['image_path']);
$viewerId  = 'viewer-' . ($item['id'] ?? bin2hex(random_bytes(3)));
?>

<?php if ($hasModel): ?>
    <!-- Le canvas 3D garde un fond noir : meilleur rendu pour les modèles + lights -->
    <div id="<?= $viewerId ?>" class="<?= $dimClass ?> border border-line bg-black rounded"
         data-model-src="<?= esc($item['model_path']) ?>"
         data-viewer="three"></div>
    <?= $this->include('partials/item_viewer_three_init') ?>
<?php elseif ($hasImage): ?>
    <img src="<?= esc($item['image_path']) ?>" alt="<?= esc($item['name'] ?? '') ?>"
         class="<?= $dimClass ?> object-contain border border-line bg-stone-100 rounded">
<?php else: ?>
    <div class="<?= $dimClass ?> border border-line bg-stone-100 flex items-center justify-center text-muted text-xs uppercase tracking-wider rounded">
        no media
    </div>
<?php endif ?>
