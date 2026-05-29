<?= $this->extend('layouts/main') ?>

<?php helper('number'); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="small mb-3">
        <a href="/jobs" class="text-muted text-decoration-none">← Tous les jobs</a>
    </div>

    <?php
        $iWorkHere = $me !== null && (string) $me['job'] === (string) $job['slug'];
        $iHaveOtherJob = $me !== null && ! empty($me['job']) && ! $iWorkHere;
        $myXp = (int) ($me['job_xp'] ?? 0);
    ?>

    <?php
        $statLabels = ['tech' => 'Tech', 'endurance' => 'Endurance', 'charisme' => 'Charisme'];
        $statsBoosted = array_filter([$job['stat_1'] ?? null, $job['stat_2'] ?? null]);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h1 class="h3 mb-1"><?= esc($job['name']) ?></h1>
            <p class="small text-muted mb-2">
                Stat dominante : <strong><?= esc($job['primary_stat'] ?? '—') ?></strong>
                <?php if (! empty($statsBoosted)): ?>
                    · Boost stats job :
                    <?php foreach ($statsBoosted as $s): ?>
                        <span class="badge bg-light text-dark border me-1"><?= esc($statLabels[$s] ?? $s) ?></span>
                    <?php endforeach ?>
                <?php endif ?>
            </p>
            <p class="mb-3"><?= esc($job['description']) ?></p>

            <?php if ($iWorkHere && $me !== null): ?>
                <div class="d-flex gap-3 small text-muted mb-3 flex-wrap">
                    <span>Tes stats jobs :</span>
                    <span><strong>Tech</strong> <span class="font-monospace"><?= (int) ($me['job_stat_tech'] ?? 0) ?></span></span>
                    <span><strong>Endurance</strong> <span class="font-monospace"><?= (int) ($me['job_stat_endurance'] ?? 0) ?></span></span>
                    <span><strong>Charisme</strong> <span class="font-monospace"><?= (int) ($me['job_stat_charisme'] ?? 0) ?></span></span>
                </div>
            <?php endif ?>

            <?php if (session()->has('message')): ?>
                <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
            <?php endif ?>
            <?php if (session()->has('error')): ?>
                <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
            <?php endif ?>

            <div class="d-flex gap-2 flex-wrap align-items-center">
                <?php if ($iWorkHere): ?>
                    <span class="text-muted small fst-italic">
                        Salaire et stats versés automatiquement chaque jour à l'heure de paie.
                    </span>
                    <form method="post" action="/jobs/quit" class="m-0" onsubmit="return confirm('Démissionner ? Tu perdras ta position et ton XP de job.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark">Démissionner</button>
                    </form>
                <?php elseif ($iHaveOtherJob): ?>
                    <p class="text-muted small fst-italic mb-0">Tu travailles déjà ailleurs (<strong><?= esc($me['job']) ?></strong>). Démissionne d'abord pour postuler ici.</p>
                <?php else: ?>
                    <form method="post" action="/jobs/<?= esc($job['slug']) ?>/apply" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">Postuler</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Hierarchie des positions -->
    <h2 class="small text-uppercase text-muted fw-semibold mb-2">Hiérarchie des positions</h2>
    <div class="card">
        <ul class="list-group list-group-flush">
            <?php foreach ($positions as $p): ?>
                <?php
                    $isMine    = $my_current !== null && (int) $my_current['id'] === (int) $p['id'];
                    $unlocked  = $iWorkHere && $myXp >= (int) $p['xp_required'];
                    $rowClass  = $isMine ? 'list-group-item-light' : '';
                ?>
                <li class="list-group-item <?= $rowClass ?> d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted font-monospace small">#<?= (int) $p['rank'] ?></span>
                            <strong><?= esc($p['name']) ?></strong>
                            <?php if ($isMine): ?>
                                <span class="badge bg-dark">actuel</span>
                            <?php elseif ($iWorkHere && $unlocked): ?>
                                <span class="badge bg-light text-muted border">débloqué</span>
                            <?php endif ?>
                        </div>
                        <?php if (! empty($p['perk_text'])): ?>
                            <div class="small text-muted mt-1">Perk : <em><?= esc($p['perk_text']) ?></em> <span class="text-muted">(à venir)</span></div>
                        <?php endif ?>
                    </div>
                    <div class="text-end small">
                        <div class="text-muted text-uppercase">Salaire</div>
                        <div class="font-monospace fw-bold">¢<?= number_format((int) $p['daily_salary']) ?>/jour</div>
                        <?php if ((int) $p['xp_required'] > 0): ?>
                            <div class="text-muted small mt-1">XP : <?= number_format((int) $p['xp_required']) ?></div>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

</div>

<?= $this->endSection() ?>
