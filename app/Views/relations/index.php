<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$statusBadge = static function (string $status): string {
    return match ($status) {
        'online'   => '<span class="badge bg-success" title="Connecté récemment">● online</span>',
        'jail'     => '<span class="badge bg-dark" title="En prison">prison</span>',
        'hospital' => '<span class="badge bg-secondary" title="Cyberclinique">clinique</span>',
        default    => '<span class="badge bg-light text-muted border" title="Hors ligne">offline</span>',
    };
};

$tabs = [
    'friend' => ['label' => 'Amis',    'icon' => 'bi-person-heart'],
    'enemy'  => ['label' => 'Ennemis', 'icon' => 'bi-person-x'],
    'target' => ['label' => 'Cibles',  'icon' => 'bi-crosshair'],
];
$current = $this->request->getGet('tab') ?? 'friend';
if (! isset($tabs[$current])) $current = 'friend';
?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Mes relations</h1>
            <p class="text-muted small mb-0">Statut online recalcule en temps reel (seuil <?= intdiv((int) $threshold, 60) ?> min sans activite).</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <?php foreach ($tabs as $key => $cfg): ?>
            <?php $count = count($grouped[$key] ?? []); ?>
            <li class="nav-item">
                <a class="nav-link <?= $current === $key ? 'active' : '' ?>"
                   href="/relations?tab=<?= esc($key, 'attr') ?>">
                    <i class="bi <?= esc($cfg['icon']) ?>"></i> <?= esc($cfg['label']) ?>
                    <span class="badge bg-light text-dark border ms-1"><?= $count ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>

    <?php $rows = $grouped[$current] ?? []; ?>
    <?php if (empty($rows)): ?>
        <p class="text-muted fst-italic small">Personne ici pour l'instant. Va sur une fiche joueur pour ajouter.</p>
    <?php else: ?>
        <div class="card">
            <ul class="list-group list-group-flush">
                <?php foreach ($rows as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div class="flex-grow-1 d-flex align-items-center gap-2">
                            <a href="/u/<?= esc($r['target_username']) ?>" class="text-dark fw-semibold text-decoration-none"><?= esc($r['target_username']) ?></a>
                            <span class="text-muted small">niv <?= (int) $r['target_level'] ?></span>
                            <?= $statusBadge((string) $r['_status']) ?>
                            <?php if (! empty($r['note'])): ?>
                                <span class="text-muted small fst-italic">« <?= esc($r['note']) ?> »</span>
                            <?php endif ?>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="/messages/thread/<?= (int) $r['target_player_id'] ?>" class="btn btn-sm btn-outline-dark" title="Envoyer un message">✉</a>
                            <?php if ($current === 'enemy' || $current === 'target'): ?>
                                <form method="post" action="/attack/<?= (int) $r['target_player_id'] ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Attaquer">⚔</button>
                                </form>
                            <?php endif ?>
                            <form method="post" action="/relations/<?= esc($current, 'attr') ?>/<?= (int) $r['target_player_id'] ?>" class="m-0"
                                  onsubmit="return confirm('Retirer cette relation ?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-dark" title="Retirer">×</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
