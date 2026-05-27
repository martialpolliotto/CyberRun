<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $item !== null;
    $title  = $isEdit ? 'Éditer : ' . esc($item['name']) : 'Nouvel item';
    $val = static function (string $field, $default = '') use ($item) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($item !== null && isset($item[$field])) return $item[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/items" class="text-decoration-none text-dark small">retour liste items</a>
    </div>

    <h1 class="h3 mb-3"><?= esc($title) ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if ($isEdit && (int) $item['discontinued'] === 1): ?>
        <?= view('partials/alert', [
            'variant' => 'warning',
            'message' => 'Cet item est HORS-CIRCUIT depuis le ' . esc($item['discontinued_at'] ?? '?') . '. Plus aucun joueur ne peut l\'équiper.',
        ]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/items/' . (int) $item['id'] . '/save' : '/admin/items/save' ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Infos -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Infos</div>
            <div class="card-body">
                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (identifiant URL, unique)', 'value' => $val('slug'), 'required' => true, 'placeholder' => 'cyberdeck-mk2']) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché',                    'value' => $val('name'), 'required' => true, 'placeholder' => 'Cyberdeck Mk.II']) ?>

                <div class="mb-3">
                    <label for="slot" class="form-label small">Slot</label>
                    <select id="slot" name="slot" required class="form-select">
                        <option value="">— choisir —</option>
                        <?php foreach ($slots as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= $val('slot') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-control" placeholder="Lore court et utile..."><?= esc($val('description')) ?></textarea>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="starter" name="starter" value="1" <?= $val('starter', 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="starter">Starter (donné à l'inscription)</label>
                </div>
            </div>
        </div>

        <!-- Vente -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Vente</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="vendor_id" class="form-label small">Marchand</label>
                        <select id="vendor_id" name="vendor_id" class="form-select">
                            <option value="">— aucun (loot, quête...) —</option>
                            <?php
                                $allVendors = model(\App\Models\VendorModel::class)->listAll();
                                $currentVendorId = (int) $val('vendor_id', 0);
                            ?>
                            <?php foreach ($allVendors as $vd): ?>
                                <option value="<?= (int) $vd['id'] ?>" <?= $currentVendorId === (int) $vd['id'] ? 'selected' : '' ?>><?= esc($vd['name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label small">Prix (crédits, 0 = pas en vente)</label>
                        <input id="price" type="number" name="price" min="0" value="<?= (int) $val('price', 0) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bonus stats -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Bonus stats</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ([
                        'bonus_force'    => 'Force',
                        'bonus_blindage' => 'Blindage',
                        'bonus_reflexes' => 'Réflexes',
                        'bonus_hack'     => 'Hack',
                    ] as $field => $label): ?>
                        <div class="col-6 col-md-3">
                            <label for="<?= $field ?>" class="form-label small"><?= esc($label) ?></label>
                            <input id="<?= $field ?>" type="number" name="<?= $field ?>" value="<?= (int) $val($field, 0) ?>" class="form-control font-monospace">
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <!-- Consommable (optionnel) -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Consommable (optionnel)</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="consumable_type" class="form-label small">Type</label>
                        <select id="consumable_type" name="consumable_type" class="form-select">
                            <?php $ct = (string) $val('consumable_type', ''); ?>
                            <option value="">— non consommable (item d'équipement) —</option>
                            <option value="booster" <?= $ct === 'booster' ? 'selected' : '' ?>>Booster (petit bonus, peu de risques)</option>
                            <option value="drug"    <?= $ct === 'drug'    ? 'selected' : '' ?>>Drogue (gros bonus, overdose + addiction)</option>
                        </select>
                        <div class="form-text">Si rempli, l'item ne pourra pas être équipé et apparaîtra dans /inventory.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="cooldown_seconds" class="form-label small">Cooldown (secondes)</label>
                        <input id="cooldown_seconds" type="number" name="cooldown_seconds" min="0" value="<?= (int) $val('cooldown_seconds', 0) ?>" class="form-control font-monospace">
                    </div>
                    <div class="col-md-4">
                        <label for="effect_duration_seconds" class="form-label small">Durée effet temporaire (sec.)</label>
                        <input id="effect_duration_seconds" type="number" name="effect_duration_seconds" min="0" value="<?= (int) $val('effect_duration_seconds', 0) ?>" class="form-control font-monospace">
                        <div class="form-text">0 = pas d'effet temporaire (juste regen instant).</div>
                    </div>
                </div>

                <div class="small text-uppercase text-muted fw-semibold mb-2">Regen instantanée (sans durée)</div>
                <div class="row g-3 mb-3">
                    <?php foreach (['effect_hp' => 'HP', 'effect_nrg' => 'NRG', 'effect_nrv' => 'NRV'] as $field => $label): ?>
                        <div class="col-md-4">
                            <label for="<?= $field ?>" class="form-label small">+<?= esc($label) ?></label>
                            <input id="<?= $field ?>" type="number" name="<?= $field ?>" value="<?= (int) $val($field, 0) ?>" class="form-control font-monospace">
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="small text-uppercase text-muted fw-semibold mb-2">Bonus temporaire de stat (additif sur la durée)</div>
                <div class="row g-3 mb-3">
                    <?php foreach (['effect_force' => 'Force', 'effect_blindage' => 'Blindage', 'effect_reflexes' => 'Réflexes', 'effect_hack' => 'Hack'] as $field => $label): ?>
                        <div class="col-6 col-md-3">
                            <label for="<?= $field ?>" class="form-label small">+<?= esc($label) ?></label>
                            <input id="<?= $field ?>" type="number" name="<?= $field ?>" value="<?= (int) $val($field, 0) ?>" class="form-control font-monospace">
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="small text-uppercase text-muted fw-semibold mb-2">Bonus temporaire de stat max</div>
                <div class="row g-3 mb-3">
                    <?php foreach (['effect_hp_max' => 'HP max', 'effect_nrg_max' => 'NRG max', 'effect_nrv_max' => 'NRV max'] as $field => $label): ?>
                        <div class="col-md-4">
                            <label for="<?= $field ?>" class="form-label small">+<?= esc($label) ?></label>
                            <input id="<?= $field ?>" type="number" name="<?= $field ?>" value="<?= (int) $val($field, 0) ?>" class="form-control font-monospace">
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="small text-uppercase text-muted fw-semibold mb-2">Drogue : addiction et overdose</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="addiction_threshold_increase" class="form-label small">Addiction +</label>
                        <input id="addiction_threshold_increase" type="number" name="addiction_threshold_increase" min="0" value="<?= (int) $val('addiction_threshold_increase', 0) ?>" class="form-control font-monospace">
                    </div>
                    <div class="col-md-3">
                        <label for="overdose_chance_pct" class="form-label small">Overdose %</label>
                        <input id="overdose_chance_pct" type="number" name="overdose_chance_pct" min="0" max="99" value="<?= (int) $val('overdose_chance_pct', 0) ?>" class="form-control font-monospace">
                    </div>
                    <div class="col-md-3">
                        <label for="overdose_hospital_min" class="form-label small">Hôpital min (min.)</label>
                        <input id="overdose_hospital_min" type="number" name="overdose_hospital_min" min="0" value="<?= (int) $val('overdose_hospital_min', 0) ?>" class="form-control font-monospace">
                    </div>
                    <div class="col-md-3">
                        <label for="overdose_hospital_max" class="form-label small">Hôpital max (min.)</label>
                        <input id="overdose_hospital_max" type="number" name="overdose_hospital_max" min="0" value="<?= (int) $val('overdose_hospital_max', 0) ?>" class="form-control font-monospace">
                    </div>
                </div>
            </div>
        </div>

        <!-- Media -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Média (optionnel)</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label small">Image (PNG/JPG/WEBP, max 2 MB)</label>
                    <input id="image" type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="form-control">
                    <?php if ($isEdit && ! empty($item['image_path'])): ?>
                        <div class="form-text">Image actuelle : <a href="<?= esc($item['image_path']) ?>" target="_blank"><?= esc(basename($item['image_path'])) ?></a></div>
                    <?php endif ?>
                </div>

                <div class="mb-3">
                    <label for="model" class="form-label small">Modèle 3D (.glb / .gltf, max 10 MB) — prend la priorité sur l'image</label>
                    <input id="model" type="file" name="model" accept=".glb,.gltf" class="form-control">
                    <?php if ($isEdit && ! empty($item['model_path'])): ?>
                        <div class="form-text">Modèle actuel : <a href="<?= esc($item['model_path']) ?>" target="_blank"><?= esc(basename($item['model_path'])) ?></a></div>
                    <?php endif ?>
                </div>

                <?php if ($isEdit && (! empty($item['image_path']) || ! empty($item['model_path']))): ?>
                    <div>
                        <div class="small text-muted text-uppercase mb-1">Aperçu</div>
                        <?= view('partials/item_viewer', ['item' => $item, 'size' => 'lg']) ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100">
            <?= $isEdit ? 'Sauvegarder' : 'Créer' ?>
        </button>
    </form>

    <?php if ($isEdit): ?>
        <!-- Statut catalogue -->
        <div class="card mt-4">
            <div class="card-header bg-light small text-uppercase fw-semibold">Statut catalogue</div>
            <div class="card-body">
                <p class="small mb-2">Actuellement détenu par <strong><?= (int) $owners ?></strong> joueur(s).</p>
                <?php if ((int) $item['discontinued'] === 0): ?>
                    <p class="small text-muted">"Mettre hors-circuit" déséquipe automatiquement tous les joueurs et empêche tout futur équipement. Item conservé en BDD.</p>
                    <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/discontinue" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark btn-sm">Mettre hors-circuit</button>
                    </form>
                <?php else: ?>
                    <p class="small text-muted">"Réintroduire" remet l'item au catalogue actif.</p>
                    <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/restore" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark btn-sm">Réintroduire au catalogue</button>
                    </form>
                <?php endif ?>
            </div>
        </div>

        <!-- Zone dangereuse -->
        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <p class="small">
                    Suppression DÉFINITIVE. L'item est retiré du catalogue ET de tous les inventaires (cascade BDD).
                    Action irréversible. Préfère "Mettre hors-circuit" dans la plupart des cas.
                </p>
                <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/destroy" onsubmit="return confirm('Supprimer DÉFINITIVEMENT cet item ? Cela cascade sur ' + <?= (int) $owners ?> + ' inventaire(s) joueur(s).')">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                        <label class="form-check-label small" for="confirm_delete">Je confirme la suppression définitive.</label>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
