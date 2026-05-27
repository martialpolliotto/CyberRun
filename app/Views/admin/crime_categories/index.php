<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Catégories de crimes</h1>
        <a href="/admin/crime-categories/new" class="btn btn-dark">+ Nouvelle catégorie</a>
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
                    <th>Ordre</th>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Stat dominante</th>
                    <th>Crimes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td class="text-center"><?= (int) $c['display_order'] ?></td>
                        <td><?= esc($c['name']) ?></td>
                        <td class="text-muted small"><?= esc($c['slug']) ?></td>
                        <td><?= esc($c['primary_stat'] ?? '—') ?></td>
                        <td class="text-center"><?= (int) ($counts[$c['id']] ?? 0) ?></td>
                        <td class="text-end">
                            <a href="/admin/crime-categories/<?= (int) $c['id'] ?>/edit" class="text-decoration-none">éditer</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" class="text-center text-muted fst-italic">Aucune catégorie.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
