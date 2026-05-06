<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-3xl mx-auto space-y-4">

    <div class="border border-warning/60 bg-warning/10 px-4 py-2 flex items-center gap-3 rounded">
        <span class="text-warning font-bold uppercase tracking-widest">[ ADMIN ]</span>
        <a href="/admin/vendors" class="text-warning/80 text-sm hover:text-warning transition">// retour marchands</a>
    </div>

    <h1 class="text-3xl font-bold text-warning">Éditer : <?= esc($vendor['name']) ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="/admin/vendors/<?= (int) $vendor['id'] ?>/save" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>

        <?= view('partials/bloc', [
            'title'   => 'Identité',
            'variant' => 'primary',
            'slot'    => view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => old('name') ?? $vendor['name'], 'required' => true])
                . '<div class="mt-3">' . view('partials/input', ['name' => 'tagline', 'label' => 'Tagline (phrase courte)', 'value' => old('tagline') ?? $vendor['tagline']]) . '</div>'
                . '<label class="block mt-3"><span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Description</span>'
                . '<textarea name="description" rows="4" class="w-full bg-surface-alt border border-line text-primary px-3 py-2 focus:border-accent focus:ring-1 focus:ring-accent focus:outline-none rounded">' . esc(old('description') ?? $vendor['description'] ?? '') . '</textarea></label>'
                . '<p class="text-xs text-muted mt-1">Slug technique : <code class="text-primary">' . esc($vendor['slug']) . '</code> (non modifiable)</p>',
        ]) ?>

        <div class="border border-line bg-surface-alt rounded p-4 space-y-3">
            <p class="text-xs text-muted uppercase tracking-wider font-semibold">Portrait</p>
            <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="w-full text-primary file:bg-stone-100 file:border file:border-line file:text-primary file:px-3 file:py-1 file:cursor-pointer file:rounded file:mr-3">
            <?php if (! empty($vendor['image_path'])): ?>
                <div class="flex items-center gap-3">
                    <img src="<?= esc($vendor['image_path']) ?>" alt="" class="w-32 h-32 object-cover rounded border border-line">
                    <p class="text-xs text-muted">Actuel : <a href="<?= esc($vendor['image_path']) ?>" target="_blank" class="text-accent underline"><?= esc(basename($vendor['image_path'])) ?></a></p>
                </div>
            <?php endif ?>
            <p class="text-xs text-muted">PNG/JPG/WEBP, max 2 MB. Affiché dans la grille des marchés et sur la page du marchand.</p>
        </div>

        <div class="border border-line bg-surface-alt rounded p-4 space-y-3">
            <p class="text-xs text-muted uppercase tracking-wider font-semibold">Bannière (optionnel)</p>
            <input type="file" name="banner" accept=".png,.jpg,.jpeg,.webp" class="w-full text-primary file:bg-stone-100 file:border file:border-line file:text-primary file:px-3 file:py-1 file:cursor-pointer file:rounded file:mr-3">
            <?php if (! empty($vendor['banner_path'])): ?>
                <img src="<?= esc($vendor['banner_path']) ?>" alt="" class="max-h-24 object-cover rounded border border-line">
            <?php endif ?>
            <p class="text-xs text-muted">Pour usage futur (bannière de page marchand). Pas affiché actuellement.</p>
        </div>

        <button type="submit" class="w-full px-4 py-3 bg-accent text-white font-medium rounded hover:bg-sky-800 transition">
            Sauvegarder
        </button>
    </form>

</div>

<?= $this->endSection() ?>
