<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Mon bazaar</h1>
            <p class="text-muted small mb-0">
                Tes annonces sont visibles sur ta fiche publique.
                Fee vendeur : <strong><?= (int) $fee_pct ?>%</strong> (sink).
                Max listings : <strong><?= (int) $max_listings ?></strong>.
            </p>
        </div>
        <a href="/u/<?= esc(auth()->user()->username) ?>" class="btn btn-outline-dark btn-sm">Voir ma fiche</a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Form : lister un item depuis l'inventaire -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold">Mettre en vente</div>
        <div class="card-body">
            <?php if (empty($listable)): ?>
                <p class="text-muted small fst-italic mb-0">Aucun item disponible (équipés ou hors-circuit exclus).</p>
            <?php else: ?>
                <form method="post" action="/bazaar/list" class="row g-2 align-items-end m-0">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label small">Item</label>
                        <select name="player_item_id" class="form-select" required>
                            <?php foreach ($listable as $row): ?>
                                <option value="<?= (int) $row['id'] ?>">
                                    <?= esc($row['item_name']) ?>
                                    (<?= (int) $row['quantity'] ?> dispo)
                                    <?= ! empty($row['item_consumable_type']) ? ' · ' . esc($row['item_consumable_type']) : '' ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Quantité</label>
                        <input type="number" name="quantity" min="1" value="1" required class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Prix unitaire (¢)</label>
                        <input type="number" name="unit_price" min="1" required class="form-control" placeholder="ex: 5000">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-dark">Lister</button>
                    </div>
                </form>
            <?php endif ?>
        </div>
    </div>

    <!-- Listings actifs -->
    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Mes listings actifs</span>
            <span class="text-muted"><?= count($listings) ?> / <?= (int) $max_listings ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Quantité</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-end">Total brut</th>
                        <th class="text-end">Net si tout vendu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $l): ?>
                        <?php
                            $brut = (int) $l['quantity'] * (int) $l['unit_price'];
                            $net  = (int) floor($brut * (100 - (int) $fee_pct) / 100);
                        ?>
                        <tr>
                            <td>
                                <strong><?= esc($l['item_name']) ?></strong>
                                <?php if (! empty($l['item_consumable_type'])): ?>
                                    <span class="badge bg-light text-dark border ms-1"><?= esc($l['item_consumable_type']) ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-end font-monospace"><?= (int) $l['quantity'] ?></td>
                            <td class="text-end font-monospace"><?= number_format((int) $l['unit_price']) ?>¢</td>
                            <td class="text-end font-monospace text-muted"><?= number_format($brut) ?>¢</td>
                            <td class="text-end font-monospace"><?= number_format($net) ?>¢</td>
                            <td class="text-end">
                                <form method="post" action="/bazaar/listings/<?= (int) $l['id'] ?>/unlist" class="m-0"
                                      onsubmit="return confirm('Retirer ce listing et récupérer les items ?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-dark">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($listings)): ?>
                        <tr><td colspan="6" class="text-center text-muted fst-italic">Aucun listing actif.</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
