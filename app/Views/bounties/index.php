<?= $this->extend('layouts/main') ?>

<?php helper('number'); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="mb-3">
        <h1 class="h3 mb-0">Primes actives</h1>
        <p class="text-muted small mb-0">Les têtes les plus chères du quartier. Le premier qui finalise empoche.</p>
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
                    <th style="width: 4rem;">#</th>
                    <th>Cible</th>
                    <th class="text-end">Montant</th>
                    <th>Placée par</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bounties as $i => $b): ?>
                    <tr>
                        <td class="text-muted font-monospace"><?= $i + 1 ?></td>
                        <td><a href="/u/<?= esc($b['target_username']) ?>" class="text-dark text-decoration-none fw-bold"><?= esc($b['target_username']) ?></a></td>
                        <td class="text-end fw-bold font-monospace">¢<?= number_format((int) $b['amount']) ?></td>
                        <td><a href="/u/<?= esc($b['placer_username']) ?>" class="text-muted text-decoration-none"><?= esc($b['placer_username']) ?></a></td>
                        <td class="small text-muted fst-italic"><?= $b['message'] !== null ? '« ' . esc($b['message']) . ' »' : '—' ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($bounties)): ?>
                    <tr><td colspan="5" class="text-center text-muted fst-italic">Aucune prime active pour l'instant.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
