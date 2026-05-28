<?= $this->extend('layouts/main') ?>

<?php
    $statusBadge = static function (string $status): string {
        return match ($status) {
            'jail'     => '<span class="badge bg-dark">En prison</span>',
            'hospital' => '<span class="badge bg-secondary">Cyberclinique</span>',
            default    => '<span class="badge bg-light text-muted">Libre</span>',
        };
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <div class="small mb-3">
        <a href="/players" class="text-muted text-decoration-none">← Tous les joueurs</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="h3 mb-1"><?= esc($profile['username']) ?></h1>
                    <p class="text-muted small mb-0">
                        Inscrit le <?= esc(\CodeIgniter\I18n\Time::parse($profile['joined_at'])->toLocalizedString('d MMMM yyyy')) ?>
                    </p>
                </div>
                <div class="text-end">
                    <?= $statusBadge((string) $profile['_status']) ?>
                </div>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <div class="small text-muted text-uppercase">Niveau</div>
                    <div class="fs-3 fw-bold mt-1"><?= (int) $profile['level'] ?></div>
                </div>
            </div>

            <?php if ($profile['_status'] === 'jail' && $me !== null && (int) $me['id'] !== (int) $profile['id']): ?>
                <hr>
                <div class="small text-uppercase text-muted fw-semibold mb-2">Le faire sortir</div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="/bust/<?= (int) $profile['id'] ?>" class="m-0" onsubmit="return confirm('Tenter un bust ? Echec = toi en prison.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark">Bust (chance <?= (int) ($profile['_bust_pct'] ?? 0) ?>%)</button>
                    </form>
                    <form method="post" action="/bail/<?= (int) $profile['id'] ?>" class="m-0" onsubmit="return confirm('Payer la caution (<?= (int) ($profile['_bail_cost'] ?? 0) ?> credits) ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">Payer caution (¢<?= number_format((int) ($profile['_bail_cost'] ?? 0)) ?>)</button>
                    </form>
                </div>
                <p class="form-text mt-2 mb-0">Bust : risqué, consomme de la nerve, échec = toi en prison. Bail : garanti, coûte des crédits.</p>
            <?php endif ?>
        </div>
    </div>

    <p class="form-text mt-3 mb-0">
        Plus d'infos sur ce joueur (combat, gangs, historique) viendront avec les prochaines mécaniques.
    </p>

</div>

<?= $this->endSection() ?>
