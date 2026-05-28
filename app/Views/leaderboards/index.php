<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $statusBadge = static function (string $status): string {
        return match ($status) {
            'jail'     => '<span class="badge bg-dark">Prison</span>',
            'hospital' => '<span class="badge bg-secondary">Cyberclinique</span>',
            default    => '<span class="badge bg-light text-muted">Libre</span>',
        };
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-3">
        <h1 class="h3 mb-0">Classements</h1>
        <p class="text-muted small mb-0">Top 20 par catégorie.</p>
    </div>

    <ul class="nav nav-tabs mb-3 flex-wrap">
        <?php foreach ($tabs as $key => $label): ?>
            <li class="nav-item">
                <a class="nav-link <?= $key === $currentTab ? 'active text-dark fw-bold' : 'text-muted' ?>"
                   href="/leaderboards/<?= esc($key) ?>">
                    <?= esc($label) ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th style="width: 4rem;">#</th>
                    <th>Pseudo</th>
                    <th>Niveau</th>
                    <th><?= esc($metric_label) ?></th>
                    <th class="text-end">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td class="text-muted font-monospace"><?= $i + 1 ?></td>
                        <td><a href="/u/<?= esc($r['username']) ?>" class="text-decoration-none text-dark fw-bold"><?= esc($r['username']) ?></a></td>
                        <td class="font-monospace"><?= (int) $r['level'] ?></td>
                        <td class="font-monospace"><?= number_format((int) $r['metric']) ?></td>
                        <td class="text-end"><?= $statusBadge((string) $r['_status']) ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted fst-italic">Aucun joueur dans ce classement pour l'instant.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
