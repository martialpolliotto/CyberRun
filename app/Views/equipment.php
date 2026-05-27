<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Équipement</h1>
            <p class="text-muted small mb-0">Un item équipé par slot.</p>
        </div>
        <div class="text-end small">
            <div class="text-muted text-uppercase">Pseudo</div>
            <div class="fw-bold"><?= esc($user->username) ?></div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Stats récap -->
    <h2 class="small text-uppercase text-muted mb-2">Stats effectives</h2>
    <div class="row g-3 mb-4">
        <?php
            $statLabels = ['force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack'];
        ?>
        <?php foreach ($statLabels as $key => $label): ?>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body p-3">
                        <div class="small text-muted text-uppercase"><?= $label ?></div>
                        <div class="fs-3 fw-bold mt-1"><?= $stats['total'][$key] ?></div>
                        <div class="small text-muted mt-1">
                            <?= $stats['base'][$key] ?><?php if ($stats['bonus'][$key] > 0): ?> + <?= $stats['bonus'][$key] ?><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Slots équipés -->
    <h2 class="small text-uppercase text-muted mb-2">Slots équipés</h2>
    <div class="row g-3 mb-4">
        <?php foreach ($slots as $slotKey => $slotLabel): ?>
            <?php $eq = $equipped[$slotKey] ?? null; ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span class="small text-muted text-uppercase"><?= $slotLabel ?></span>
                            <?php if ($eq): ?>
                                <form method="post" action="/equipment/unequip/<?= esc($slotKey) ?>" class="d-inline m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-muted p-0">déséquiper</button>
                                </form>
                            <?php endif ?>
                        </div>
                        <?php if ($eq): ?>
                            <div class="fw-bold mt-1"><?= esc($eq['item_name']) ?></div>
                            <div class="small mt-1"><?= view('partials/bonus_inline', ['item' => $eq]) ?></div>
                            <?php if (! empty($eq['item_description'])): ?>
                                <div class="text-muted small fst-italic mt-2"><?= esc($eq['item_description']) ?></div>
                            <?php endif ?>
                        <?php else: ?>
                            <div class="text-muted fst-italic mt-1">(aucun équipé)</div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Inventaire disponible -->
    <h2 class="small text-uppercase text-muted mb-2">Inventaire disponible</h2>
    <?php if (empty($available)): ?>
        <p class="text-muted fst-italic small">Aucun item disponible (tout est équipé).</p>
    <?php else: ?>
        <?php foreach ($slots as $slotKey => $slotLabel): ?>
            <?php $items = $available[$slotKey] ?? []; ?>
            <?php if (empty($items)) continue; ?>
            <div class="mb-3">
                <div class="small text-muted text-uppercase mb-1"><?= $slotLabel ?></div>
                <ul class="list-group">
                    <?php foreach ($items as $it): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?= esc($it['item_name']) ?></div>
                                <div class="small"><?= view('partials/bonus_inline', ['item' => $it]) ?></div>
                            </div>
                            <form method="post" action="/equipment/equip/<?= (int) $it['id'] ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-dark">Équiper</button>
                            </form>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endforeach ?>
    <?php endif ?>

    <!-- Cache obsolète -->
    <?php if (! empty($obsolete)): ?>
        <h2 class="small text-uppercase text-muted mt-4 mb-2">Cache obsolète <span class="fw-normal text-muted">(items hors-circuit)</span></h2>
        <ul class="list-group">
            <?php foreach ($obsolete as $it): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center text-muted">
                    <div>
                        <div class="fw-bold"><?= esc($it['item_name']) ?></div>
                        <div class="small"><?= view('partials/bonus_inline', ['item' => $it]) ?></div>
                    </div>
                    <span class="small fst-italic">hors-circuit</span>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
