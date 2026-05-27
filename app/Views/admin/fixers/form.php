<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $fixer !== null;
    $val = static function (string $field, $default = '') use ($fixer) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($fixer !== null && isset($fixer[$field])) return $fixer[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/fixers" class="text-decoration-none text-dark small">retour fixers</a>
    </div>

    <h1 class="h3 mb-3"><?= $isEdit ? 'Éditer : ' . esc($fixer['name']) : 'Nouveau fixer' ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/fixers/' . (int) $fixer['id'] . '/save' : '/admin/fixers/save' ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Identité</div>
            <div class="card-body">
                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (identifiant URL, unique)', 'value' => $val('slug'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => $val('name'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'tagline', 'label' => 'Tagline', 'value' => $val('tagline')]) ?>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control"><?= esc($val('description')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="unlock_order" class="form-label small">Ordre de déblocage</label>
                    <input id="unlock_order" type="number" name="unlock_order" min="1" value="<?= (int) $val('unlock_order', 1) ?>" class="form-control">
                    <div class="form-text">Le fixer N est débloqué quand toutes les missions des fixers d'ordre &lt; N sont claimed. 1 = toujours débloqué.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Portrait</div>
            <div class="card-body">
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="form-control mb-2">
                <?php if ($isEdit && ! empty($fixer['image_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <img src="<?= esc($fixer['image_path']) ?>" alt="" class="object-fit-cover border" style="width: 8rem; height: 8rem;">
                        <p class="form-text mb-0">Actuel : <a href="<?= esc($fixer['image_path']) ?>" target="_blank"><?= esc(basename($fixer['image_path'])) ?></a></p>
                    </div>
                <?php endif ?>
                <p class="form-text mt-2">PNG/JPG/WEBP, max 2 MB.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100"><?= $isEdit ? 'Sauvegarder' : 'Créer' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <div class="card mt-4">
            <div class="card-header bg-light small text-uppercase fw-semibold">Missions de ce fixer</div>
            <div class="card-body">
                <a href="/admin/missions/new" class="btn btn-outline-dark btn-sm mb-2">+ Ajouter une mission</a>
                <p class="form-text mb-0">Va dans <a href="/admin/missions">Gestion des missions</a> pour voir/éditer la liste complète.</p>
            </div>
        </div>

        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <p class="small">Suppression définitive. Cascade sur toutes les missions et les progressions joueur.</p>
                <form method="post" action="/admin/fixers/<?= (int) $fixer['id'] ?>/destroy" onsubmit="return confirm('Supprimer définitivement ce fixer et toutes ses missions ?')">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                        <label class="form-check-label small" for="confirm_delete">Je confirme.</label>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
