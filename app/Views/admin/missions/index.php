<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Missions</h1>
        <a href="/admin/missions/new" class="btn btn-dark">+ Nouvelle mission</a>
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
                    <th>Fixer</th>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Objectif</th>
                    <th>Récompense</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($missions as $m): ?>
                    <tr>
                        <td><?= esc($m['fixer_name']) ?></td>
                        <td class="text-center"><?= (int) $m['mission_order'] ?></td>
                        <td><?= esc($m['name']) ?> <span class="text-muted small">(<?= esc($m['slug']) ?>)</span></td>
                        <td>
                            <span class="font-monospace small"><?= esc($m['objective_type']) ?></span>
                            <span class="text-muted small">→ <?= esc($m['objective_target']) ?> × <?= (int) $m['objective_count'] ?></span>
                        </td>
                        <td class="small">¢<?= number_format((int) $m['reward_credits']) ?> · <?= (int) $m['reward_xp'] ?> XP</td>
                        <td class="text-end">
                            <a href="/admin/missions/<?= (int) $m['id'] ?>/edit" class="text-decoration-none">éditer</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($missions)): ?>
                    <tr><td colspan="6" class="text-center text-muted fst-italic">Aucune mission.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
