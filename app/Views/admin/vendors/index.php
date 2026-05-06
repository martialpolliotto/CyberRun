<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-5xl mx-auto space-y-4">

    <div class="border border-warning/60 bg-warning/10 px-4 py-2 flex items-center gap-3 rounded">
        <span class="text-warning font-bold uppercase tracking-widest">[ ADMIN ]</span>
        <a href="/admin" class="text-warning/80 text-sm hover:text-warning transition">// retour dashboard</a>
    </div>

    <h1 class="text-3xl font-bold text-warning">Marchands</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($vendors as $v): ?>
            <a href="/admin/vendors/<?= (int) $v['id'] ?>/edit" class="block border border-line bg-surface-alt rounded p-4 hover:border-accent hover:shadow transition">
                <?php if (! empty($v['image_path'])): ?>
                    <img src="<?= esc($v['image_path']) ?>" alt="" class="w-full h-32 object-cover rounded mb-3 bg-stone-100">
                <?php else: ?>
                    <div class="w-full h-32 bg-stone-100 border border-line rounded mb-3 flex items-center justify-center text-muted text-xs">
                        pas d'image
                    </div>
                <?php endif ?>
                <p class="text-primary font-bold"><?= esc($v['name']) ?></p>
                <p class="text-muted text-xs mt-1"><?= esc($v['slug']) ?></p>
                <p class="text-accent text-xs italic mt-2">« <?= esc($v['tagline'] ?? '—') ?> »</p>
            </a>
        <?php endforeach ?>
    </div>

</div>

<?= $this->endSection() ?>
