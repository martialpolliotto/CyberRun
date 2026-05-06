<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $playerCredits = $player !== null ? (int) $player['credits'] : 0;
?>

<?= $this->section('content') ?>

<div class="max-w-5xl mx-auto space-y-6">

    <div class="text-sm">
        <a href="/shops" class="text-accent hover:text-sky-900 transition">← Tous les marchés</a>
    </div>

    <!-- Header marchand -->
    <div class="border border-line bg-surface-alt rounded p-5 flex flex-col md:flex-row gap-5">
        <?php if (! empty($vendor['image_path'])): ?>
            <img src="<?= esc($vendor['image_path']) ?>" alt="<?= esc($vendor['name']) ?>"
                 class="w-full md:w-48 h-48 object-cover rounded bg-stone-100">
        <?php else: ?>
            <div class="w-full md:w-48 h-48 bg-stone-100 border border-line rounded flex items-center justify-center text-muted text-xs uppercase tracking-wider">
                portrait à venir
            </div>
        <?php endif ?>
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-primary"><?= esc($vendor['name']) ?></h1>
            <?php if (! empty($vendor['tagline'])): ?>
                <p class="text-accent italic mt-1">« <?= esc($vendor['tagline']) ?> »</p>
            <?php endif ?>
            <?php if (! empty($vendor['description'])): ?>
                <p class="text-primary/80 text-sm mt-3"><?= esc($vendor['description']) ?></p>
            <?php endif ?>
        </div>
    </div>

    <!-- Flash -->
    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Catalogue -->
    <div>
        <h2 class="text-xs uppercase tracking-wider text-muted mb-2 font-semibold">Catalogue</h2>
        <?php if (empty($catalog)): ?>
            <p class="text-muted italic text-sm">Aucun item en stock pour le moment.</p>
        <?php else: ?>
            <div class="border border-line bg-surface-alt rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 text-muted uppercase text-xs tracking-wider">
                        <tr>
                            <th class="text-left p-3">Item</th>
                            <th class="text-left p-3">Slot</th>
                            <th class="text-left p-3">Bonus</th>
                            <th class="text-right p-3">Prix</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catalog as $it): ?>
                            <?php $canAfford = $playerCredits >= (int) $it['price']; ?>
                            <tr class="border-t border-line">
                                <td class="p-3">
                                    <p class="text-primary font-bold"><?= esc($it['name']) ?></p>
                                    <?php if (! empty($it['description'])): ?>
                                        <p class="text-muted text-xs italic mt-1"><?= esc($it['description']) ?></p>
                                    <?php endif ?>
                                </td>
                                <td class="p-3 text-muted"><?= esc(\App\Models\ItemModel::SLOTS[$it['slot']] ?? $it['slot']) ?></td>
                                <td class="p-3"><?= view('partials/bonus_inline', ['item' => $it]) ?></td>
                                <td class="p-3 text-right text-credits font-bold tabular-nums">¢<?= number_format($it['price']) ?></td>
                                <td class="p-3 text-right">
                                    <form method="post" action="/shop/<?= esc($vendor['slug']) ?>/buy/<?= (int) $it['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                <?= $canAfford ? '' : 'disabled' ?>
                                                class="px-3 py-1 text-sm font-medium rounded transition <?= $canAfford
                                                    ? 'bg-accent text-white hover:bg-sky-800 cursor-pointer'
                                                    : 'bg-stone-200 text-muted cursor-not-allowed' ?>">
                                            <?= $canAfford ? 'Acheter' : '✗ Crédits' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <p class="text-xs text-muted mt-2">Solde actuel : <span class="text-credits font-bold tabular-nums">¢<?= number_format($playerCredits) ?></span></p>
    </div>

</div>

<?= $this->endSection() ?>
