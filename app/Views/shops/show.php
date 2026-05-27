<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $playerCredits = $player !== null ? (int) $player['credits'] : 0;
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="small mb-3">
        <a href="/shops" class="text-muted text-decoration-none">← Tous les marchés</a>
    </div>

    <!-- Header marchand -->
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row gap-3">
            <?php if (! empty($vendor['image_path'])): ?>
                <img src="<?= esc($vendor['image_path']) ?>" alt="<?= esc($vendor['name']) ?>"
                     class="object-fit-cover bg-light border" style="width: 12rem; height: 12rem;">
            <?php else: ?>
                <div class="bg-light border d-flex align-items-center justify-content-center text-muted small text-uppercase" style="width: 12rem; height: 12rem;">
                    portrait à venir
                </div>
            <?php endif ?>
            <div class="flex-grow-1">
                <h1 class="h3 mb-1"><?= esc($vendor['name']) ?></h1>
                <?php if (! empty($vendor['tagline'])): ?>
                    <p class="fst-italic mb-2">« <?= esc($vendor['tagline']) ?> »</p>
                <?php endif ?>
                <?php if (! empty($vendor['description'])): ?>
                    <p class="small mb-0"><?= esc($vendor['description']) ?></p>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Catalogue -->
    <h2 class="small text-uppercase text-muted fw-semibold mb-2">Catalogue</h2>
    <?php if (empty($catalog)): ?>
        <p class="text-muted fst-italic small">Aucun item en stock pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Slot</th>
                        <th>Bonus</th>
                        <th class="text-end">Prix</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalog as $it): ?>
                        <?php $canAfford = $playerCredits >= (int) $it['price']; ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= esc($it['name']) ?></div>
                                <?php if (! empty($it['description'])): ?>
                                    <div class="text-muted small fst-italic"><?= esc($it['description']) ?></div>
                                <?php endif ?>
                            </td>
                            <td class="text-muted"><?= esc(\App\Models\ItemModel::SLOTS[$it['slot']] ?? $it['slot']) ?></td>
                            <td><?= view('partials/bonus_inline', ['item' => $it]) ?></td>
                            <td class="text-end fw-bold font-monospace">¢<?= number_format($it['price']) ?></td>
                            <td class="text-end">
                                <form method="post" action="/shop/<?= esc($vendor['slug']) ?>/buy/<?= (int) $it['id'] ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            <?= $canAfford ? '' : 'disabled' ?>
                                            class="btn btn-sm btn-dark">
                                        <?= $canAfford ? 'Acheter' : 'Crédits' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <p class="small text-muted mt-2">Solde : <span class="fw-bold font-monospace">¢<?= number_format($playerCredits) ?></span></p>

</div>

<?= $this->endSection() ?>
