<?= $this->extend('layouts/main') ?>

<?php helper('number'); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-3">
        <h1 class="h3 mb-0">Jobs</h1>
        <p class="text-muted small mb-0">Trouve un boulot en ville. Salaire horaire automatique, plus tu travailles plus tu montes.</p>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php if ($me !== null && ! empty($me['job'])): ?>
        <div class="alert alert-light border d-flex justify-content-between align-items-center">
            <span>Job actuel : <a href="/jobs/<?= esc($me['job']) ?>" class="text-dark fw-bold text-decoration-none"><?= esc($me['job']) ?></a> · XP : <span class="font-monospace"><?= number_format((int) $me['job_xp']) ?></span></span>
            <form method="post" action="/jobs/quit" class="m-0" onsubmit="return confirm('Démissionner ? Tu perdras ta position et ton XP de job.');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-dark">Démissionner</button>
            </form>
        </div>
    <?php endif ?>

    <div class="row g-3">
        <?php foreach ($jobs as $j): ?>
            <div class="col-md-6">
                <a href="/jobs/<?= esc($j['slug']) ?>" class="card text-decoration-none text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <h2 class="h6 mb-0"><?= esc($j['name']) ?></h2>
                            <span class="small text-muted font-monospace">¢<?= number_format((int) $j['_starting_salary']) ?>/jour</span>
                        </div>
                        <p class="small text-muted mb-1">Stat dominante : <strong><?= esc($j['primary_stat'] ?? '—') ?></strong></p>
                        <p class="small mb-0"><?= esc($j['description']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

</div>

<?= $this->endSection() ?>
