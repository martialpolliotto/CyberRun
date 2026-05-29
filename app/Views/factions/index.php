<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Factions</h1>
            <p class="text-muted small mb-0">Crews persistants. Rejoins-en une ou fonde la tienne.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($me !== null && empty($me['faction_id'])): ?>
                <a href="/factions/create" class="btn btn-dark btn-sm">+ Fonder</a>
            <?php elseif ($me !== null && ! empty($me['faction_id'])): ?>
                <a href="/factions/mine" class="btn btn-dark btn-sm">Ma faction</a>
            <?php endif ?>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if ($my_pending !== null): ?>
        <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 mb-3">
            <span class="small">
                Candidature en attente chez
                <a href="/factions/<?= (int) $my_pending['faction_id'] ?>" class="text-dark fw-bold"><?= esc($my_pending['faction_name']) ?></a>
                <span class="text-muted">[<?= esc($my_pending['faction_tag']) ?>]</span>.
            </span>
            <form method="post" action="/factions/applications/mine/cancel" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-dark btn-sm">Annuler</button>
            </form>
        </div>
    <?php endif ?>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Classement par respect</span>
            <span class="text-muted">
                Fonder : <?= number_format($create_cost) ?>¢ + niv <?= (int) $create_min_level ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Faction</th>
                        <th>Tag</th>
                        <th>Leader</th>
                        <th class="text-end">Membres</th>
                        <th class="text-end">Respect</th>
                        <th class="text-end">Trésorerie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 0; foreach ($factions as $f): $rank++; ?>
                        <tr>
                            <td class="text-muted font-monospace"><?= $rank ?></td>
                            <td><a href="/factions/<?= (int) $f['id'] ?>" class="fw-bold text-dark text-decoration-none"><?= esc($f['name']) ?></a></td>
                            <td><span class="badge bg-dark font-monospace">[<?= esc($f['tag']) ?>]</span></td>
                            <td>
                                <?php if (! empty($f['leader_username'])): ?>
                                    <a href="/u/<?= esc($f['leader_username']) ?>" class="text-dark text-decoration-none"><?= esc($f['leader_username']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td class="text-end font-monospace"><?= (int) $f['members_count'] ?></td>
                            <td class="text-end font-monospace"><?= number_format((int) $f['respect']) ?></td>
                            <td class="text-end font-monospace text-muted"><?= number_format((int) $f['treasury']) ?>¢</td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($factions)): ?>
                        <tr><td colspan="7" class="text-center text-muted fst-italic">Pas encore de faction. Sois le premier à en fonder une.</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
