<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $crime !== null;
    $val = static function (string $field, $default = '') use ($crime) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($crime !== null && isset($crime[$field])) return $crime[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/crimes" class="text-decoration-none text-dark small">retour crimes</a>
    </div>

    <h1 class="h3 mb-3"><?= $isEdit ? 'Éditer : ' . esc($crime['name']) : 'Nouveau crime' ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/crimes/' . (int) $crime['id'] . '/save' : '/admin/crimes/save' ?>">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Identité</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="category_id" class="form-label small">Catégorie parente</label>
                    <select id="category_id" name="category_id" required class="form-select">
                        <option value="">— choisir —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $val('category_id', 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (URL, unique)', 'value' => $val('slug'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => $val('name'), 'required' => true]) ?>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description (visible dans la liste, avant tentative)</label>
                    <textarea id="description" name="description" rows="3" class="form-control"><?= esc($val('description')) ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="min_category_xp" class="form-label small">XP catégorie minimum (palier de déblocage)</label>
                        <input id="min_category_xp" type="number" name="min_category_xp" min="0" value="<?= (int) $val('min_category_xp', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="nerve_cost" class="form-label small">Coût en nerve</label>
                        <input id="nerve_cost" type="number" name="nerve_cost" min="1" value="<?= (int) $val('nerve_cost', 1) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>


        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Probabilités</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="base_success_pct" class="form-label small">Base réussite (%)</label>
                        <input id="base_success_pct" type="number" name="base_success_pct" min="0" max="99" value="<?= (int) $val('base_success_pct', 50) ?>" class="form-control">
                        <div class="form-text">Ajusté par +stat/2 + cat_xp/10 + bonus horaire. Cap à 95%.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="critical_fail_pct" class="form-label small">Échec critique (%)</label>
                        <input id="critical_fail_pct" type="number" name="critical_fail_pct" min="0" max="99" value="<?= (int) $val('critical_fail_pct', 5) ?>" class="form-control">
                        <div class="form-text">Roll indépendant qui passe en premier.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Récompenses (réussite)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="reward_credits_min" class="form-label small">Crédits min</label>
                        <input id="reward_credits_min" type="number" name="reward_credits_min" min="0" value="<?= (int) $val('reward_credits_min', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_credits_max" class="form-label small">Crédits max</label>
                        <input id="reward_credits_max" type="number" name="reward_credits_max" min="0" value="<?= (int) $val('reward_credits_max', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_xp" class="form-label small">XP joueur</label>
                        <input id="reward_xp" type="number" name="reward_xp" min="0" value="<?= (int) $val('reward_xp', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_category_xp" class="form-label small">XP catégorie</label>
                        <input id="reward_category_xp" type="number" name="reward_category_xp" min="0" value="<?= (int) $val('reward_category_xp', 1) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Conséquences (échec critique)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="critical_destination" class="form-label small">Destination</label>
                        <select id="critical_destination" name="critical_destination" class="form-select">
                            <option value="jail"     <?= $val('critical_destination', 'jail') === 'jail'     ? 'selected' : '' ?>>Prison</option>
                            <option value="hospital" <?= $val('critical_destination', 'jail') === 'hospital' ? 'selected' : '' ?>>Cyberclinique</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="critical_minutes_min" class="form-label small">Minutes min</label>
                        <input id="critical_minutes_min" type="number" name="critical_minutes_min" min="0" value="<?= (int) $val('critical_minutes_min', 5) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="critical_minutes_max" class="form-label small">Minutes max</label>
                        <input id="critical_minutes_max" type="number" name="critical_minutes_max" min="0" value="<?= (int) $val('critical_minutes_max', 15) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Bonus horaire (optionnel)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="time_bonus_pct" class="form-label small">Bonus (%)</label>
                        <input id="time_bonus_pct" type="number" name="time_bonus_pct" min="0" value="<?= (int) $val('time_bonus_pct', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="time_bonus_hour_start" class="form-label small">Heure début (0-23)</label>
                        <input id="time_bonus_hour_start" type="number" name="time_bonus_hour_start" min="0" max="23" value="<?= $val('time_bonus_hour_start', '') === null ? '' : esc((string) $val('time_bonus_hour_start', '')) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="time_bonus_hour_end" class="form-label small">Heure fin (0-23)</label>
                        <input id="time_bonus_hour_end" type="number" name="time_bonus_hour_end" min="0" max="23" value="<?= $val('time_bonus_hour_end', '') === null ? '' : esc((string) $val('time_bonus_hour_end', '')) ?>" class="form-control">
                    </div>
                </div>
                <div class="form-text mt-2">Si la fenêtre est active à l'heure de la tentative, +bonus% sur le taux de réussite. Une fenêtre qui wrap minuit est supportée (ex: début=22, fin=5 ⇒ 22h-5h).</div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100"><?= $isEdit ? 'Sauvegarder' : 'Créer' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <?php
            $outcomeLabels = [
                'success'  => ['Réussite',         'Affiché quand la tentative réussit. Les chiffres (crédits, XP) sont ajoutés automatiquement après le texte. Une variante au hasard sera piochée à chaque tentative.'],
                'fail'     => ['Échec simple',     'Affiché quand le crime rate sans conséquence sérieuse (juste la nerve consommée). Une variante au hasard sera piochée.'],
                'critical' => ['Échec critique',   'Affiché quand le joueur part en prison ou à la cyberclinique. La destination + durée sont ajoutées automatiquement.'],
            ];
        ?>
        <h2 id="texts" class="h5 mt-4 mb-2">Scénarios narratifs</h2>
        <p class="small text-muted mb-3">Tu peux ajouter <strong>plusieurs variantes</strong> par issue. Le système en pioche une au hasard à chaque tentative.</p>

        <?php foreach ($outcomeLabels as $key => [$label, $help]): ?>
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-semibold"><?= esc($label) ?></span>
                    <span class="badge bg-dark"><?= count($texts[$key]) ?> variante<?= count($texts[$key]) > 1 ? 's' : '' ?></span>
                </div>
                <div class="card-body">
                    <p class="form-text mb-3"><?= esc($help) ?></p>

                    <?php if (empty($texts[$key])): ?>
                        <p class="text-muted fst-italic small mb-3">Aucune variante. Si tu n'en ajoutes pas, un texte par défaut générique sera affiché au joueur.</p>
                    <?php else: ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($texts[$key] as $t): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                                    <div class="flex-grow-1" style="white-space: pre-wrap;"><?= esc($t['text']) ?></div>
                                    <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/texts/<?= (int) $t['id'] ?>/destroy" class="m-0" onsubmit="return confirm('Supprimer cette variante ?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">supprimer</button>
                                    </form>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>

                    <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/texts/add">
                        <?= csrf_field() ?>
                        <input type="hidden" name="outcome" value="<?= esc($key) ?>">
                        <div class="mb-2">
                            <textarea name="text" rows="3" class="form-control" placeholder="Nouvelle variante…" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">+ Ajouter cette variante</button>
                    </form>
                </div>
            </div>
        <?php endforeach ?>

        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/destroy" onsubmit="return confirm('Supprimer définitivement ce crime ?')">
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
