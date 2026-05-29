<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><?= esc($faction['name']) ?> <span class="text-muted">[<?= esc($faction['tag']) ?>]</span></h1>
            <p class="text-muted small mb-0">
                <?= $is_leader ? 'Tu en es le leader.' : 'Membre depuis le ' . esc(substr((string) ($members[0]['joined_at'] ?? ''), 0, 10)) . '.' ?>
                · <a href="/factions/<?= (int) $faction['id'] ?>" class="text-dark">page publique</a>
            </p>
        </div>
        <form method="post" action="/factions/mine/leave" class="m-0"
              onsubmit="return confirm('<?= $is_leader && (int) $faction['members_count'] <= 1 ? 'Dissoudre la faction ?' : 'Quitter la faction ?' ?>');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-dark btn-sm">
                <?= $is_leader && (int) $faction['members_count'] <= 1 ? 'Dissoudre' : 'Quitter' ?>
            </button>
        </form>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-center"><div class="card-body p-3">
                <div class="small text-muted text-uppercase">Membres</div>
                <div class="fs-3 fw-bold mt-1"><?= (int) $faction['members_count'] ?></div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center"><div class="card-body p-3">
                <div class="small text-muted text-uppercase">Respect</div>
                <div class="fs-3 fw-bold mt-1"><?= number_format((int) $faction['respect']) ?></div>
            </div></div>
        </div>
        <div class="col-6 col-md-6">
            <div class="card"><div class="card-body p-3">
                <div class="small text-muted text-uppercase mb-1">Trésorerie</div>
                <div class="d-flex align-items-baseline justify-content-between mb-2">
                    <span class="fs-3 fw-bold"><?= number_format((int) $faction['treasury']) ?>¢</span>
                </div>
                <form method="post" action="/factions/mine/donate" class="d-flex gap-2 m-0">
                    <?= csrf_field() ?>
                    <input type="number" name="amount" min="1" class="form-control form-control-sm" placeholder="Montant">
                    <button type="submit" class="btn btn-dark btn-sm">Donner</button>
                </form>
            </div></div>
        </div>
    </div>

    <?php if ($is_leader): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                <span>Candidatures</span>
                <span class="text-muted"><?= count($applications) ?> en attente</span>
            </div>
            <ul class="list-group list-group-flush small">
                <?php if (empty($applications)): ?>
                    <li class="list-group-item text-muted fst-italic text-center">Aucune candidature pour l'instant.</li>
                <?php endif ?>
                <?php foreach ($applications as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div>
                                <a href="/u/<?= esc($a['username']) ?>" class="text-dark text-decoration-none fw-bold"><?= esc($a['username']) ?></a>
                                <span class="text-muted">— niv <?= (int) $a['level'] ?></span>
                            </div>
                            <?php if (! empty($a['message'])): ?>
                                <div class="text-muted fst-italic mt-1">« <?= esc($a['message']) ?> »</div>
                            <?php endif ?>
                        </div>
                        <div class="d-flex gap-1">
                            <form method="post" action="/factions/applications/<?= (int) $a['id'] ?>/accept" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-dark btn-sm">Accepter</button>
                            </form>
                            <form method="post" action="/factions/applications/<?= (int) $a['id'] ?>/reject" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-dark btn-sm">Refuser</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Membres</span>
            <span class="text-muted"><?= count($members) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Joueur</th>
                        <th>Rang</th>
                        <th class="text-end">Niveau</th>
                        <th class="text-end">Respect</th>
                        <th class="text-end">Crédits</th>
                        <?php if ($is_leader): ?><th></th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><a href="/u/<?= esc($m['username']) ?>" class="text-dark text-decoration-none fw-bold"><?= esc($m['username']) ?></a></td>
                            <td>
                                <?php if ($m['rank'] === 'leader'): ?>
                                    <span class="badge bg-dark">leader</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">membre</span>
                                <?php endif ?>
                            </td>
                            <td class="text-end font-monospace"><?= (int) $m['level'] ?></td>
                            <td class="text-end font-monospace"><?= number_format((int) $m['contributed_respect']) ?></td>
                            <td class="text-end font-monospace text-muted"><?= number_format((int) $m['contributed_credits']) ?>¢</td>
                            <?php if ($is_leader): ?>
                                <td class="text-end">
                                    <?php if ($m['rank'] !== 'leader'): ?>
                                        <form method="post" action="/factions/members/<?= (int) $m['player_id'] ?>/kick" class="m-0"
                                              onsubmit="return confirm('Exclure ce membre ?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-dark">×</button>
                                        </form>
                                    <?php endif ?>
                                </td>
                            <?php endif ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
