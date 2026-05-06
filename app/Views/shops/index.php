<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-primary">Marchés</h1>
        <p class="text-muted text-sm mt-1">Visite les commerces du quartier pour t'équiper.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($vendors as $v): ?>
            <a href="/shop/<?= esc($v['slug']) ?>" class="block border border-line bg-surface-alt rounded p-4 hover:border-accent hover:shadow transition">
                <?php if (! empty($v['image_path'])): ?>
                    <img src="<?= esc($v['image_path']) ?>" alt="<?= esc($v['name']) ?>"
                         class="w-full h-40 object-cover rounded mb-3 bg-stone-100">
                <?php else: ?>
                    <div class="w-full h-40 bg-stone-100 border border-line rounded mb-3 flex items-center justify-center text-muted text-xs uppercase tracking-wider">
                        portrait à venir
                    </div>
                <?php endif ?>
                <h2 class="text-lg font-bold text-primary"><?= esc($v['name']) ?></h2>
                <?php if (! empty($v['tagline'])): ?>
                    <p class="text-accent text-sm italic mt-1">« <?= esc($v['tagline']) ?> »</p>
                <?php endif ?>
                <?php if (! empty($v['description'])): ?>
                    <p class="text-muted text-sm mt-2 line-clamp-3"><?= esc($v['description']) ?></p>
                <?php endif ?>
            </a>
        <?php endforeach ?>
    </div>

</div>

<?= $this->endSection() ?>
