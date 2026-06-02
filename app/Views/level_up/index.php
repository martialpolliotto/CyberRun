<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 40rem;">

    <h1 class="h3 mb-3 text-center"><i class="bi bi-arrow-up-circle"></i> Passage de niveau</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card mb-3">
        <div class="card-body text-center">
            <div class="small text-muted text-uppercase mb-1">Niveau actuel</div>
            <div class="display-4 fw-bold mb-2"><?= (int) $me['level'] ?></div>
            <div class="small text-muted">
                XP : <strong class="font-monospace"><?= number_format((int) $me['xp']) ?></strong> / <?= number_format($threshold) ?>
            </div>
        </div>
    </div>

    <?php if ($can_level): ?>
        <div class="card mb-3 border-dark">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold text-center">
                Tu peux passer niveau <?= (int) $me['level'] + 1 ?>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    En montant tu gagnes <strong>+<?= (int) $bonus ?> hp_max</strong> et tu es soigné à fond.
                    L'XP excédentaire (<?= number_format((int) $me['xp'] - $threshold) ?>) est reportée sur le niveau suivant.
                </p>
                <p class="small text-muted mb-3">
                    Choisir <em>« plus tard »</em> garde ton niveau actuel : pas de bonus Life, mais tu restes au palier <?= (int) $me['level'] ?> (utile si certains contenus sont locks par niveau — ex. système de voyage à venir au niveau 15).
                    L'XP continue de s'accumuler.
                </p>

                <div class="d-flex justify-content-between gap-2">
                    <a href="/profile" class="btn btn-outline-dark">Plus tard</a>
                    <form method="post" action="/level-up/perform" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">↑ Passer niveau <?= (int) $me['level'] + 1 ?></button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ((int) $me['xp'] >= 2 * $threshold): ?>
            <p class="small text-muted text-center fst-italic">
                Note : tu as plus que assez d'XP pour plusieurs niveaux. Chaque clic = 1 niveau.
            </p>
        <?php endif ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center text-muted">
                Encore <strong><?= number_format($threshold - (int) $me['xp']) ?> XP</strong> pour pouvoir passer au niveau <?= (int) $me['level'] + 1 ?>.
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
