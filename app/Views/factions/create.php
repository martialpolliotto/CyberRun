<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 40rem;">

    <h1 class="h3 mb-3">Fonder une faction</h1>

    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-body">
            <p class="small text-muted mb-3">
                Coût : <strong><?= number_format($cost) ?> crédits</strong> · Niveau minimum : <strong><?= (int) $min_level ?></strong>.
                Tu deviens automatiquement le leader. Tu pourras kicker / accepter des candidatures.
            </p>

            <form method="post" action="/factions/create" class="m-0">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small">Nom de la faction</label>
                    <input type="text" name="name" maxlength="<?= (int) $name_max ?>" required
                           class="form-control"
                           value="<?= esc(old('name')) ?>"
                           placeholder="Les Spectres de Night City">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Tag (<?= (int) $tag_max ?> caractères max)</label>
                    <input type="text" name="tag" maxlength="<?= (int) $tag_max ?>" required
                           class="form-control font-monospace"
                           value="<?= esc(old('tag')) ?>"
                           placeholder="SPCTR" style="text-transform: uppercase;">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Description (optionnel)</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Ce qui fait ta faction. Style, ambitions, recrutement…"><?= esc(old('description')) ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/factions" class="btn btn-outline-dark btn-sm">Annuler</a>
                    <button type="submit" class="btn btn-dark">Fonder pour <?= number_format($cost) ?>¢</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
