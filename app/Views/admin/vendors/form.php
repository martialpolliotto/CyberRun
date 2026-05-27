<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/vendors" class="text-decoration-none text-dark small">retour marchands</a>
    </div>

    <h1 class="h3 mb-3">Éditer : <?= esc($vendor['name']) ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="/admin/vendors/<?= (int) $vendor['id'] ?>/save" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Identité</div>
            <div class="card-body">
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => old('name') ?? $vendor['name'], 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'tagline', 'label' => 'Tagline (phrase courte)', 'value' => old('tagline') ?? $vendor['tagline']]) ?>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control"><?= esc(old('description') ?? $vendor['description'] ?? '') ?></textarea>
                </div>

                <p class="form-text">Slug technique : <code><?= esc($vendor['slug']) ?></code> (non modifiable)</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Portrait</div>
            <div class="card-body">
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="form-control mb-2">
                <?php if (! empty($vendor['image_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <img src="<?= esc($vendor['image_path']) ?>" alt="" class="object-fit-cover border" style="width: 8rem; height: 8rem;">
                        <p class="form-text mb-0">Actuel : <a href="<?= esc($vendor['image_path']) ?>" target="_blank"><?= esc(basename($vendor['image_path'])) ?></a></p>
                    </div>
                <?php endif ?>
                <p class="form-text mt-2">PNG/JPG/WEBP, max 2 MB.</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Bannière (optionnel)</div>
            <div class="card-body">
                <input type="file" name="banner" accept=".png,.jpg,.jpeg,.webp" class="form-control mb-2">
                <?php if (! empty($vendor['banner_path'])): ?>
                    <img src="<?= esc($vendor['banner_path']) ?>" alt="" class="object-fit-cover border mt-2" style="max-height: 6rem;">
                <?php endif ?>
                <p class="form-text mt-2">Pour usage futur. Pas affiché actuellement.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100">Sauvegarder</button>
    </form>

</div>

<?= $this->endSection() ?>
