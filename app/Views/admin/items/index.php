<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Items</h1>
        <a href="/admin/items/new" class="btn btn-dark">+ Nouvel item</a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Slot</th>
                    <th>Bonus</th>
                    <th class="text-center">Starter</th>
                    <th class="text-center">Joueurs</th>
                    <th class="text-center">Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr <?= $it['discontinued'] ? 'class="text-muted"' : '' ?>>
                        <td><?= esc($it['name']) ?> <span class="text-muted small">(<?= esc($it['slug']) ?>)</span></td>
                        <td><?= esc($slots[$it['slot']] ?? $it['slot']) ?></td>
                        <td><?= view('partials/bonus_inline', ['item' => $it]) ?></td>
                        <td class="text-center"><?= $it['starter'] ? '★' : '·' ?></td>
                        <td class="text-center"><?= (int) ($owners[$it['id']] ?? 0) ?></td>
                        <td class="text-center"><?= $it['discontinued'] ? 'hors-circuit' : 'actif' ?></td>
                        <td class="text-end">
                            <a href="/admin/items/<?= (int) $it['id'] ?>/edit" class="text-decoration-none">éditer</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted fst-italic">Aucun item au catalogue.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
