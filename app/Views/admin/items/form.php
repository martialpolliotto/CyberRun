<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $item !== null;
    $title  = $isEdit ? 'Éditer : ' . esc($item['name']) : 'Nouvel item';
    // Helper pour récupérer la valeur soit du POST échoué (old), soit de l'item, soit défaut
    $val = static function (string $field, $default = '') use ($item) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($item !== null && isset($item[$field])) return $item[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="max-w-3xl mx-auto space-y-4">

    <div class="border border-warning/60 bg-warning/10 px-4 py-2 flex items-center gap-3">
        <span class="text-warning font-bold uppercase tracking-widest">[ ADMIN ]</span>
        <a href="/admin/items" class="text-warning/80 text-sm hover:text-warning transition">// retour liste items</a>
    </div>

    <h1 class="text-3xl font-bold text-warning">&gt; <?= esc($title) ?></h1>

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

    <!-- Formulaire principal (champs + upload) -->
    <form method="post" action="<?= $isEdit ? '/admin/items/' . (int) $item['id'] . '/save' : '/admin/items/save' ?>" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>

        <div class="border border-primary/40 bg-primary/5 p-4 space-y-3">
            <p class="text-xs text-primary/60 uppercase tracking-wider">&gt; INFOS</p>

            <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (identifiant URL, unique)', 'value' => $val('slug'), 'required' => true, 'placeholder' => 'cyberdeck-mk2']) ?>
            <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché',                    'value' => $val('name'), 'required' => true, 'placeholder' => 'Cyberdeck Mk.II']) ?>

            <label class="block">
                <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Slot</span>
                <select name="slot" required class="w-full bg-surface-alt border border-primary/40 text-primary px-3 py-2 focus:border-accent focus:outline-none">
                    <option value="">— choisir —</option>
                    <?php foreach ($slots as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= $val('slot') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
            </label>

            <label class="block">
                <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Description</span>
                <textarea name="description" rows="3" class="w-full bg-surface-alt border border-primary/40 text-primary px-3 py-2 focus:border-accent focus:outline-none placeholder:text-primary/30 font-mono" placeholder="Lore court et utile..."><?= esc($val('description')) ?></textarea>
            </label>

            <label class="flex items-center gap-2 text-sm text-primary/80 cursor-pointer">
                <input type="checkbox" name="starter" value="1" <?= $val('starter', 0) ? 'checked' : '' ?> class="accent-accent">
                Marquer comme starter (donné automatiquement à l'inscription)
            </label>
        </div>

        <div class="border border-primary/40 bg-primary/5 p-4 space-y-3">
            <p class="text-xs text-primary/60 uppercase tracking-wider">&gt; VENTE</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block">
                    <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Marchand</span>
                    <select name="vendor_id" class="w-full bg-surface-alt border border-primary/40 text-primary px-3 py-2 focus:border-accent focus:outline-none rounded">
                        <option value="">— aucun (loot, quête...) —</option>
                        <?php
                            $allVendors = model(\App\Models\VendorModel::class)->listAll();
                            $currentVendorId = (int) $val('vendor_id', 0);
                        ?>
                        <?php foreach ($allVendors as $vd): ?>
                            <option value="<?= (int) $vd['id'] ?>" <?= $currentVendorId === (int) $vd['id'] ? 'selected' : '' ?>><?= esc($vd['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label class="block">
                    <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Prix (crédits, 0 = pas en vente)</span>
                    <input type="number" name="price" min="0" value="<?= (int) $val('price', 0) ?>" class="w-full bg-surface-alt border border-primary/40 text-primary px-3 py-2 focus:border-accent focus:outline-none rounded">
                </label>
            </div>
        </div>

        <div class="border border-primary/40 bg-primary/5 p-4 space-y-3">
            <p class="text-xs text-primary/60 uppercase tracking-wider">&gt; BONUS_STATS</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ([
                    'bonus_force'    => 'Force',
                    'bonus_blindage' => 'Blindage',
                    'bonus_reflexes' => 'Réflexes',
                    'bonus_hack'     => 'Hack',
                ] as $field => $label): ?>
                    <label class="block">
                        <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1"><?= esc($label) ?></span>
                        <input type="number" name="<?= $field ?>" value="<?= (int) $val($field, 0) ?>" class="w-full bg-surface-alt border border-primary/40 text-primary px-3 py-2 focus:border-accent focus:outline-none font-mono">
                    </label>
                <?php endforeach ?>
            </div>
        </div>

        <div class="border border-primary/40 bg-primary/5 p-4 space-y-3">
            <p class="text-xs text-primary/60 uppercase tracking-wider">&gt; MEDIA (optionnel)</p>

            <label class="block">
                <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Image (PNG/JPG/WEBP, max 2 MB)</span>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="w-full text-primary/80 file:bg-primary/10 file:border file:border-primary/40 file:text-primary file:px-3 file:py-1 file:cursor-pointer">
                <?php if ($isEdit && ! empty($item['image_path'])): ?>
                    <p class="text-xs text-success mt-1">// Image actuelle : <a href="<?= esc($item['image_path']) ?>" target="_blank" class="underline"><?= esc(basename($item['image_path'])) ?></a></p>
                <?php endif ?>
            </label>

            <label class="block">
                <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1">Modèle 3D (.glb / .gltf, max 10 MB) — prend la priorité sur l'image</span>
                <input type="file" name="model" accept=".glb,.gltf" class="w-full text-primary/80 file:bg-primary/10 file:border file:border-primary/40 file:text-primary file:px-3 file:py-1 file:cursor-pointer">
                <?php if ($isEdit && ! empty($item['model_path'])): ?>
                    <p class="text-xs text-success mt-1">// Modèle actuel : <a href="<?= esc($item['model_path']) ?>" target="_blank" class="underline"><?= esc(basename($item['model_path'])) ?></a></p>
                <?php endif ?>
            </label>

            <?php if ($isEdit && (! empty($item['image_path']) || ! empty($item['model_path']))): ?>
                <div class="mt-2">
                    <p class="text-xs text-primary/60 uppercase tracking-wider mb-1">Aperçu</p>
                    <?= view('partials/item_viewer', ['item' => $item, 'size' => 'lg']) ?>
                </div>
            <?php endif ?>
        </div>

        <button type="submit" class="w-full px-4 py-3 border border-accent bg-accent text-white font-bold uppercase tracking-wider hover:bg-sky-800 transition">
            <?= $isEdit ? 'Sauvegarder' : 'Créer' ?>
        </button>
    </form>

    <?php if ($isEdit): ?>
        <!-- Statut catalogue : actif <-> hors-circuit -->
        <div class="border border-warning/40 bg-warning/5 p-4 space-y-2">
            <p class="text-xs text-warning/80 uppercase tracking-wider">&gt; STATUT_CATALOGUE</p>
            <p class="text-sm text-primary/80">Actuellement détenu par <strong class="text-primary"><?= (int) $owners ?></strong> joueur(s).</p>
            <?php if ((int) $item['discontinued'] === 0): ?>
                <p class="text-sm text-warning">"Mettre hors-circuit" déséquipe automatiquement tous les joueurs et empêche tout futur équipement. Item conservé en BDD.</p>
                <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/discontinue">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-4 py-2 border border-warning bg-warning/20 text-warning hover:bg-warning hover:text-black transition uppercase tracking-wider text-sm">
                        Mettre hors-circuit
                    </button>
                </form>
            <?php else: ?>
                <p class="text-sm text-success">"Réintroduire" remet l'item au catalogue actif (les joueurs ne sont PAS automatiquement ré-équipés).</p>
                <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/restore">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-4 py-2 border border-success bg-success/20 text-success hover:bg-success hover:text-black transition uppercase tracking-wider text-sm">
                        Réintroduire au catalogue
                    </button>
                </form>
            <?php endif ?>
        </div>

        <!-- ZONE DANGEREUSE : hard delete -->
        <div class="border-2 border-danger/60 bg-danger/10 p-4 space-y-3">
            <p class="text-danger font-bold uppercase tracking-wider">⚠ ZONE DANGEREUSE</p>
            <p class="text-sm text-danger/90">
                Suppression DÉFINITIVE. L'item est retiré du catalogue ET de tous les inventaires (cascade BDD).
                Action irréversible. Préfère "Mettre hors-circuit" dans la plupart des cas.
            </p>
            <form method="post" action="/admin/items/<?= (int) $item['id'] ?>/destroy" onsubmit="return confirm('Supprimer DÉFINITIVEMENT cet item ? Cela cascade sur ' + <?= (int) $owners ?> + ' inventaire(s) joueur(s).')">
                <?= csrf_field() ?>
                <label class="flex items-center gap-2 text-sm text-danger cursor-pointer">
                    <input type="checkbox" name="confirm_delete" value="1" required class="accent-danger">
                    Je confirme la suppression définitive (cascade sur tous les joueurs).
                </label>
                <button type="submit" class="mt-2 px-4 py-2 border border-danger bg-danger text-white hover:bg-red-700 transition uppercase tracking-wider text-sm">
                    Supprimer définitivement
                </button>
            </form>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
