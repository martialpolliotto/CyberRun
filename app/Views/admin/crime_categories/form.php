<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $category !== null;
    $val = static function (string $field, $default = '') use ($category) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($category !== null && isset($category[$field])) return $category[$field];
        return $default;
    };
    $stats = ['' => '— aucune —', 'force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack'];
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/crime-categories" class="text-decoration-none text-dark small">retour catégories</a>
    </div>

    <h1 class="h3 mb-3"><?= $isEdit ? 'Éditer : ' . esc($category['name']) : 'Nouvelle catégorie' ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/crime-categories/' . (int) $category['id'] . '/save' : '/admin/crime-categories/save' ?>">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-body">
                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (URL, unique)', 'value' => $val('slug'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => $val('name'), 'required' => true]) ?>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control"><?= esc($val('description')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="primary_stat" class="form-label small">Stat dominante (bonus +stat/2 au succès)</label>
                    <select id="primary_stat" name="primary_stat" class="form-select" style="max-width: 16rem;">
                        <?php $current = (string) $val('primary_stat', ''); ?>
                        <?php foreach ($stats as $k => $label): ?>
                            <option value="<?= esc($k) ?>" <?= $current === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="display_order" class="form-label small">Ordre d'affichage</label>
                    <input id="display_order" type="number" name="display_order" min="1" value="<?= (int) $val('display_order', 1) ?>" class="form-control" style="max-width: 8rem;">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100"><?= $isEdit ? 'Sauvegarder' : 'Créer' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <p class="small">Suppression définitive. Cascade sur tous les crimes de la catégorie + progressions joueur.</p>
                <form method="post" action="/admin/crime-categories/<?= (int) $category['id'] ?>/destroy" onsubmit="return confirm('Supprimer définitivement cette catégorie et tous ses crimes ?')">
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
