<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <h1 class="h3 mb-3">Paramètres du jeu</h1>
    <p class="text-muted small mb-3">Centralise les coefficients ajustables (coûts, multiplicateurs, durées). Les valeurs sont chargées à la volée par le code via <code>GameSettingModel::get()</code>.</p>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="/admin/game-settings/save">
        <?= csrf_field() ?>

        <?php foreach ($grouped as $category => $rows): ?>
            <div class="card mb-3">
                <div class="card-header bg-light small text-uppercase fw-semibold"><?= esc($category) ?></div>
                <div class="card-body">
                    <table class="table table-borderless align-middle mb-0">
                        <thead class="small text-muted text-uppercase">
                            <tr>
                                <th style="width: 30%;">Paramètre</th>
                                <th style="width: 18%;">Type</th>
                                <th style="width: 20%;">Valeur</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= esc($r['label']) ?></div>
                                        <code class="small text-muted"><?= esc($r['setting_key']) ?></code>
                                    </td>
                                    <td><span class="badge bg-light text-muted"><?= esc($r['type']) ?></span></td>
                                    <td>
                                        <input type="text" name="values[<?= esc($r['setting_key']) ?>]"
                                               value="<?= esc($r['value']) ?>"
                                               class="form-control form-control-sm font-monospace">
                                    </td>
                                    <td class="small text-muted"><?= esc($r['description'] ?? '') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach ?>

        <button type="submit" class="btn btn-dark w-100">Sauvegarder tous les paramètres</button>
    </form>

</div>

<?= $this->endSection() ?>
