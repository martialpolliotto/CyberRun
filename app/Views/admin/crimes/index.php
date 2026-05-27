<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <h1 class="h3 mb-0">Crimes</h1>
        <a href="/admin/crimes/new" class="btn btn-dark">+ Nouveau crime</a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white small">
            <thead class="table-light">
                <tr>
                    <th>Catégorie</th>
                    <th>Nom</th>
                    <th>Min XP cat.</th>
                    <th>Nerve</th>
                    <th>Base %</th>
                    <th>Crit %</th>
                    <th>Crédits</th>
                    <th>XP</th>
                    <th>Crit. dest.</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($crimes as $c): ?>
                    <tr>
                        <td><?= esc($c['category_name']) ?></td>
                        <td><?= esc($c['name']) ?> <span class="text-muted">(<?= esc($c['slug']) ?>)</span></td>
                        <td><?= (int) $c['min_category_xp'] ?></td>
                        <td><?= (int) $c['nerve_cost'] ?></td>
                        <td><?= (int) $c['base_success_pct'] ?>%</td>
                        <td><?= (int) $c['critical_fail_pct'] ?>%</td>
                        <td>¢<?= (int) $c['reward_credits_min'] ?>–<?= (int) $c['reward_credits_max'] ?></td>
                        <td>+<?= (int) $c['reward_xp'] ?> / +<?= (int) $c['reward_category_xp'] ?></td>
                        <td><?= esc($c['critical_destination']) ?> (<?= (int) $c['critical_minutes_min'] ?>–<?= (int) $c['critical_minutes_max'] ?>m)</td>
                        <td class="text-end"><a href="/admin/crimes/<?= (int) $c['id'] ?>/edit" class="text-decoration-none">éditer</a></td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($crimes)): ?>
                    <tr><td colspan="10" class="text-center text-muted fst-italic">Aucun crime.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
