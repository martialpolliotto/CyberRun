<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-check"></i> Dailies</h1>
            <p class="text-muted small mb-0">
                3 défis quotidiens. Les mêmes pour tout le monde aujourd'hui. Reset à minuit.
            </p>
        </div>
        <div class="text-end small">
            <div class="text-muted text-uppercase">Date</div>
            <div class="fw-bold font-monospace"><?= esc(date('Y-m-d')) ?></div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="d-flex flex-column gap-3">
        <?php foreach ($dailies as $d):
            $pct = (int) $d['objective_count'] > 0
                ? min(100, (int) round(((int) $d['progress'] / (int) $d['objective_count']) * 100))
                : 0;
            $isCompleted = $d['completed_at'] !== null;
            $isClaimed   = $d['claimed_at']   !== null;
        ?>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="flex-grow-1" style="min-width: 16rem;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h6 mb-0"><?= esc($d['template_name']) ?></h2>
                                <?php if ($isClaimed): ?>
                                    <span class="badge bg-secondary">réclamé</span>
                                <?php elseif ($isCompleted): ?>
                                    <span class="badge bg-success">prêt à réclamer</span>
                                <?php endif ?>
                            </div>
                            <?php if (! empty($d['template_description'])): ?>
                                <p class="small text-muted mb-2"><?= esc($d['template_description']) ?></p>
                            <?php endif ?>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Progression</span>
                                <span class="font-monospace"><?= (int) $d['progress'] ?> / <?= (int) $d['objective_count'] ?></span>
                            </div>
                            <div class="progress cr-bar-notched" style="height: 6px;">
                                <div class="progress-bar cr-bar-mission" style="width: <?= $pct ?>%"></div>
                            </div>
                            <div class="small text-muted mt-2">
                                Récompense : <strong>+<?= number_format((int) $d['reward_credits']) ?>¢</strong>
                                · <strong>+<?= (int) $d['reward_xp'] ?> XP</strong>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <?php if ($isClaimed): ?>
                                <button type="button" class="btn btn-outline-secondary" disabled>✓ Réclamée</button>
                            <?php elseif ($isCompleted): ?>
                                <form method="post" action="/dailies/<?= (int) $d['id'] ?>/claim" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-dark">Réclamer</button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-dark" disabled>En cours</button>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>

        <?php if (empty($dailies)): ?>
            <p class="text-muted fst-italic text-center small">Aucune daily aujourd'hui (pool vide ?).</p>
        <?php endif ?>
    </div>

</div>

<?= $this->endSection() ?>
