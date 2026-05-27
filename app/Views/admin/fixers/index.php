<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Fixers</h1>
        <a href="/admin/fixers/new" class="btn btn-dark">+ Nouveau fixer</a>
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
                    <th>Ordre unlock</th>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Missions</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fixers as $f): ?>
                    <tr>
                        <td class="text-center"><?= (int) $f['unlock_order'] ?></td>
                        <td><?= esc($f['name']) ?></td>
                        <td class="text-muted small"><?= esc($f['slug']) ?></td>
                        <td class="text-center"><?= (int) ($missionCounts[$f['id']] ?? 0) ?></td>
                        <td class="text-end">
                            <a href="/admin/fixers/<?= (int) $f['id'] ?>/edit" class="text-decoration-none">éditer</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($fixers)): ?>
                    <tr><td colspan="5" class="text-center text-muted fst-italic">Aucun fixer.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
