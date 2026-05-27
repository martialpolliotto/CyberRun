<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $mission !== null;
    $val = static function (string $field, $default = '') use ($mission) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($mission !== null && isset($mission[$field])) return $mission[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/missions" class="text-decoration-none text-dark small">retour missions</a>
    </div>

    <h1 class="h3 mb-3"><?= $isEdit ? 'Éditer : ' . esc($mission['name']) : 'Nouvelle mission' ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/missions/' . (int) $mission['id'] . '/save' : '/admin/missions/save' ?>">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Identité</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="fixer_id" class="form-label small">Fixer parent</label>
                    <select id="fixer_id" name="fixer_id" required class="form-select">
                        <option value="">— choisir —</option>
                        <?php foreach ($fixers as $f): ?>
                            <option value="<?= (int) $f['id'] ?>" <?= (int) $val('fixer_id', 0) === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= esc($f['name']) ?> (unlock #<?= (int) $f['unlock_order'] ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (URL, unique)', 'value' => $val('slug'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => $val('name'), 'required' => true]) ?>

                <div class="mb-3">
                    <label for="mission_order" class="form-label small">Ordre dans la chaîne du fixer</label>
                    <input id="mission_order" type="number" name="mission_order" min="1" value="<?= (int) $val('mission_order', 1) ?>" class="form-control" style="max-width: 8rem;">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Textes narratifs</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="brief" class="form-label small">Brief (donné au joueur quand il accepte la mission)</label>
                    <textarea id="brief" name="brief" rows="6" class="form-control"><?= esc($val('brief')) ?></textarea>
                    <div class="form-text">Pose le décor, explique ce que le fixer attend. 1 à 3 phrases.</div>
                </div>

                <div class="mb-3">
                    <label for="outro" class="form-label small">Outro (affiché quand la mission est terminée, avant la réclamation)</label>
                    <textarea id="outro" name="outro" rows="6" class="form-control"><?= esc($val('outro')) ?></textarea>
                    <div class="form-text">Le mot de fin du fixer quand le joueur revient avec le travail accompli. Optionnel.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Objectif</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="objective_type" class="form-label small">Type</label>
                    <select id="objective_type" name="objective_type" required class="form-select">
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= $val('objective_type') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="objective_target" class="form-label small">Cible</label>
                    <input id="objective_target" type="text" name="objective_target" value="<?= esc($val('objective_target', '*')) ?>" class="form-control font-monospace">
                    <div class="form-text">
                        Selon le type :
                        <code>*</code> (n'importe quoi),
                        <code>force/blindage/reflexes/hack</code> (stat),
                        <code>profile/lab/shops/equipment/fixers</code> (page),
                        <code>armurerie/ripperdoc/friperie</code> (vendor),
                        slot d'item, etc.
                        Pour <code>reach_level</code>, la cible est ignorée.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="objective_count" class="form-label small">Count / Seuil</label>
                    <input id="objective_count" type="number" name="objective_count" min="1" value="<?= (int) $val('objective_count', 1) ?>" class="form-control">
                    <div class="form-text">Pour les compteurs : nombre d'occurrences. Pour les seuils (reach_*) : la valeur à atteindre.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Récompenses</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="reward_credits" class="form-label small">Crédits</label>
                        <input id="reward_credits" type="number" name="reward_credits" min="0" value="<?= (int) $val('reward_credits', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="reward_xp" class="form-label small">XP</label>
                        <input id="reward_xp" type="number" name="reward_xp" min="0" value="<?= (int) $val('reward_xp', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="reward_item_id" class="form-label small">Item (optionnel)</label>
                        <select id="reward_item_id" name="reward_item_id" class="form-select">
                            <option value="">— aucun —</option>
                            <?php foreach ($items as $it): ?>
                                <option value="<?= (int) $it['id'] ?>" <?= (int) $val('reward_item_id', 0) === (int) $it['id'] ? 'selected' : '' ?>><?= esc($it['name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100"><?= $isEdit ? 'Sauvegarder' : 'Créer' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <p class="small">Suppression définitive. Cascade sur les progressions joueur.</p>
                <form method="post" action="/admin/missions/<?= (int) $mission['id'] ?>/destroy" onsubmit="return confirm('Supprimer définitivement cette mission ?')">
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
