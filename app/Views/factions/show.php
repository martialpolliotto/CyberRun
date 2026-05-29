<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><?= esc($faction['name']) ?> <span class="text-muted">[<?= esc($faction['tag']) ?>]</span></h1>
            <p class="text-muted small mb-0">
                Fondée le <?= esc(substr((string) $faction['created_at'], 0, 10)) ?> par
                <?php if (! empty($faction['leader_username'])): ?>
                    <a href="/u/<?= esc($faction['leader_username']) ?>" class="text-dark"><?= esc($faction['leader_username']) ?></a>
                <?php else: ?>
                    <span>inconnu</span>
                <?php endif ?>
            </p>
        </div>
        <a href="/factions" class="text-decoration-none text-muted small">‹ retour</a>
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
        <div class="col-6 col-md-3">
            <div class="card text-center"><div class="card-body p-3">
                <div class="small text-muted text-uppercase">Trésorerie</div>
                <div class="fs-3 fw-bold mt-1"><?= number_format((int) $faction['treasury']) ?>¢</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center"><div class="card-body p-3">
                <div class="small text-muted text-uppercase">Tag</div>
                <div class="fs-3 fw-bold mt-1 font-monospace">[<?= esc($faction['tag']) ?>]</div>
            </div></div>
        </div>
    </div>

    <?php if (! empty($faction['description'])): ?>
        <div class="card mb-3"><div class="card-body small" style="white-space: pre-wrap;"><?= esc($faction['description']) ?></div></div>
    <?php endif ?>

    <?php if ($me !== null && ! $is_member && empty($me['faction_id']) && $my_pending === null): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Postuler</div>
            <div class="card-body">
                <form method="post" action="/factions/<?= (int) $faction['id'] ?>/apply" class="m-0">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <textarea name="message" rows="2" maxlength="500"
                                  class="form-control"
                                  placeholder="Message au leader (optionnel)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">Envoyer ma candidature</button>
                </form>
            </div>
        </div>
    <?php elseif ($my_pending !== null && (int) $my_pending['faction_id'] === (int) $faction['id']): ?>
        <div class="alert alert-light border small">Ta candidature est en attente de validation par le leader.</div>
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
                        <th class="text-end">Contrib.</th>
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
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
