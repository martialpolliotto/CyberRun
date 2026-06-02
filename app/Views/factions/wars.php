<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper('number'); ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-fire"></i> Guerres de factions</h1>
            <p class="text-muted small mb-0">Toutes les guerres actives et récemment terminées.</p>
        </div>
        <a href="/factions" class="text-decoration-none text-muted small">‹ Toutes les factions</a>
    </div>

    <?php if (empty($wars)): ?>
        <p class="text-muted fst-italic small">Aucune guerre en cours. Calme avant la tempête.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($wars as $w):
                $isActive = $w['status'] === 'active';
                $isPending = $w['status'] === 'pending';
                $isEnded   = str_starts_with($w['status'], 'ended_');
                $winnerSide = match ($w['status']) {
                    'ended_a_won' => 'a',
                    'ended_b_won' => 'b',
                    default       => null,
                };
                $pot = (int) $w['stake_a'] + (int) $w['stake_b'];
            ?>
                <div class="card <?= $isActive ? 'border-dark' : '' ?>">
                    <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                        <span>
                            <?php if ($isActive): ?><span class="badge bg-dark">en cours</span>
                            <?php elseif ($isPending): ?><span class="badge bg-warning text-dark">déclarée</span>
                            <?php elseif ($w['status'] === 'ended_draw'): ?><span class="badge bg-secondary">égalité</span>
                            <?php elseif ($isEnded): ?><span class="badge bg-secondary">terminée</span>
                            <?php endif ?>
                            Pot : <?= number_format($pot) ?>¢
                        </span>
                        <?php if ($isActive): ?>
                            <span class="text-muted small">Fin : <?= esc(substr((string) $w['ends_at'], 0, 16)) ?></span>
                        <?php elseif ($isEnded): ?>
                            <span class="text-muted small">Terminée le <?= esc(substr((string) $w['ended_at'], 0, 16)) ?></span>
                        <?php endif ?>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center text-center">
                            <div class="col-5 <?= $winnerSide === 'a' ? 'fw-bold' : '' ?>">
                                <a href="/factions/<?= (int) $w['faction_a_id'] ?>" class="text-dark text-decoration-none">
                                    <div class="small text-muted">[<?= esc($w['faction_a_tag']) ?>]</div>
                                    <div class="fs-5"><?= esc($w['faction_a_name']) ?></div>
                                </a>
                                <div class="fs-2 fw-bold font-monospace mt-1"><?= (int) $w['score_a'] ?></div>
                                <?php if ($winnerSide === 'a'): ?><div class="small text-muted">vainqueur</div><?php endif ?>
                            </div>
                            <div class="col-2 text-muted">VS</div>
                            <div class="col-5 <?= $winnerSide === 'b' ? 'fw-bold' : '' ?>">
                                <a href="/factions/<?= (int) $w['faction_b_id'] ?>" class="text-dark text-decoration-none">
                                    <div class="small text-muted">[<?= esc($w['faction_b_tag']) ?>]</div>
                                    <div class="fs-5"><?= esc($w['faction_b_name']) ?></div>
                                </a>
                                <div class="fs-2 fw-bold font-monospace mt-1"><?= (int) $w['score_b'] ?></div>
                                <?php if ($winnerSide === 'b'): ?><div class="small text-muted">vainqueur</div><?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
