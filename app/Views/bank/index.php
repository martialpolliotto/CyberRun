<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php helper(['number', 'time']); ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-bank"></i> Banque</h1>
            <p class="text-muted small mb-0">
                Place tes crédits sur 7/14/21/28 jours. <strong>Dépôt verrouillé jusqu'à maturité</strong>, pas de retrait anticipé.
            </p>
        </div>
        <div class="text-end small">
            <div class="text-muted text-uppercase">Solde wallet</div>
            <div class="fw-bold font-monospace"><?= number_format((int) $me['credits']) ?>¢</div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Form depot -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold">Nouveau dépôt</div>
        <div class="card-body">
            <form method="post" action="/bank/deposit" class="row g-2 align-items-end m-0">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label small">Montant (¢)</label>
                    <input type="number" name="amount" min="1" max="<?= (int) $me['credits'] ?>"
                           required class="form-control" placeholder="ex: 50000">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Durée</label>
                    <select name="duration_days" class="form-select" required>
                        <?php foreach ($durations as $d): ?>
                            <?php $pctLabel = rtrim(rtrim(number_format($d['pct'], 2, '.', ''), '0'), '.'); ?>
                            <option value="<?= (int) $d['days'] ?>"><?= (int) $d['days'] ?> jours — <?= $pctLabel ?>%</option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-dark"
                            onclick="return confirm('Confirmer le dépôt ? Tu ne pourras pas retirer avant maturité.');">Déposer</button>
                </div>
            </form>
            <p class="form-text mt-2 mb-0">Max <?= (int) $max_active ?> dépôts actifs simultanés. <strong>Verrouillé jusqu'à maturité.</strong></p>
        </div>
    </div>

    <!-- Liste depots -->
    <h2 class="small text-uppercase text-muted mb-2">Tes dépôts</h2>

    <?php
    $active     = array_values(array_filter($deposits, fn($d) => $d['_status'] === 'active'));
    $matured    = array_values(array_filter($deposits, fn($d) => $d['_status'] === 'matured'));
    $withdrawn  = array_values(array_filter($deposits, fn($d) => $d['_status'] === 'withdrawn'));
    ?>

    <?php foreach ([['matured', 'Prêts à retirer', $matured], ['active', 'En cours', $active], ['withdrawn', 'Historique', $withdrawn]] as [$key, $title, $list]): ?>
        <?php if (! empty($list)): ?>
            <div class="card mb-3">
                <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                    <span><?= esc($title) ?></span>
                    <span class="text-muted"><?= count($list) ?></span>
                </div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($list as $d): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div class="flex-grow-1">
                                <div>
                                    <strong class="font-monospace"><?= number_format((int) $d['amount']) ?>¢</strong>
                                    @ <strong><?= rtrim(rtrim(number_format((float) $d['interest_pct'], 2, '.', ''), '0'), '.') ?>%</strong>
                                    sur <?= (int) $d['duration_days'] ?>j
                                    → <span class="text-muted">intérêt <?= number_format((int) $d['_interest']) ?>¢</span>
                                </div>
                                <div class="text-muted">
                                    <?php if ($d['_status'] === 'withdrawn'): ?>
                                        Retiré le <?= esc(substr((string) $d['withdrawn_at'], 0, 16)) ?> · payé <?= number_format((int) $d['withdrawn_amount']) ?>¢
                                    <?php elseif ($d['_status'] === 'matured'): ?>
                                        Maturé le <?= esc(substr((string) $d['matures_at'], 0, 16)) ?>
                                    <?php else: ?>
                                        Maturité dans <?= esc(relative_short($d['matures_at'])) ?> (<?= esc(substr((string) $d['matures_at'], 0, 16)) ?>)
                                    <?php endif ?>
                                </div>
                            </div>
                            <?php if ($d['_status'] === 'matured'): ?>
                                <form method="post" action="/bank/deposits/<?= (int) $d['id'] ?>/withdraw" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-dark btn-sm">Retirer <?= number_format((int) $d['_payout_now']) ?>¢</button>
                                </form>
                            <?php elseif ($d['_status'] === 'active'): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="bi bi-lock"></i> Verrouillé
                                </button>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <?php if (empty($deposits)): ?>
        <p class="text-muted fst-italic small">Aucun dépôt pour l'instant. Place tes premières économies ci-dessus.</p>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
