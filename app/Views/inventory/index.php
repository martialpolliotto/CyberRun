<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $now = \CodeIgniter\I18n\Time::now();

    $cooldownRemaining = static function (?string $lastAt, int $cooldown) use ($now): int {
        if (empty($lastAt) || $cooldown <= 0) return 0;
        $end = \CodeIgniter\I18n\Time::parse($lastAt)->addSeconds($cooldown);
        if ($end->isBefore($now)) return 0;
        return $end->getTimestamp() - $now->getTimestamp();
    };

    $effectsByKind = ['booster' => null, 'drug' => null];
    foreach ($activeEffects as $e) {
        $effectsByKind[$e['kind']] = $e;
    }

    $addiction = (int) $player['addiction_level'];
    $tier      = \App\Models\PlayerModel::addictionTier($addiction);

    $categoryLabels = \App\Models\ItemModel::CATEGORIES;

    $categoryIcons = [
        'all'        => 'bi-grid-3x3-gap',
        'equipped'   => 'bi-shield-check',
        'available'  => 'bi-box-seam',
        'weapon'     => 'bi-lightning-charge',
        'protection' => 'bi-shield',
        'cyberware'  => 'bi-cpu',
        'booster'    => 'bi-rocket-takeoff',
        'drug'       => 'bi-capsule',
    ];

    $rowIcon = static function (array $r) use ($categoryIcons): string {
        $cat = $r['_category'] ?? 'all';
        return $categoryIcons[$cat] ?? 'bi-box';
    };

    $formatRemaining = static function (int $seconds): string {
        if ($seconds <= 0) return 'prêt';
        $m = intdiv($seconds, 60); $s = $seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
    };

    $bonusInline = static function (array $r): string {
        $parts = [];
        foreach (['force' => 'F', 'blindage' => 'B', 'reflexes' => 'R', 'hack' => 'H'] as $stat => $code) {
            $v = (int) ($r['bonus_' . $stat] ?? 0);
            if ($v !== 0) $parts[] = sprintf('%+d %s', $v, $code);
        }
        return $parts === [] ? '—' : implode(' ', $parts);
    };

    $effectsInline = static function (array $r): string {
        $parts = [];
        if ((int) $r['effect_hp']  > 0) $parts[] = '+' . (int) $r['effect_hp']  . ' Life';
        if ((int) $r['effect_nrg'] > 0) $parts[] = '+' . (int) $r['effect_nrg'] . ' NRG';
        if ((int) $r['effect_nrv'] > 0) $parts[] = '+' . (int) $r['effect_nrv'] . ' NRV';
        foreach (['force' => 'F', 'blindage' => 'B', 'reflexes' => 'R', 'hack' => 'H'] as $stat => $code) {
            $v = (int) ($r['effect_' . $stat] ?? 0);
            if ($v > 0) $parts[] = '+' . $v . ' ' . $code;
        }
        return $parts === [] ? '—' : implode(', ', $parts);
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <h1 class="h3 mb-3">Inventaire</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Effets actifs (condense) -->
    <div class="row g-2 mb-3">
        <?php foreach (['booster' => 'Booster actif', 'drug' => 'Drogue active'] as $kind => $label): ?>
            <div class="col-md-6">
                <div class="card h-100"><div class="card-body py-2">
                    <?php $e = $effectsByKind[$kind] ?? null; ?>
                    <?php if ($e === null): ?>
                        <span class="text-muted fst-italic small"><?= esc($label) ?> : aucun.</span>
                    <?php else: ?>
                        <?php $remaining = max(0, \CodeIgniter\I18n\Time::parse($e['expires_at'])->getTimestamp() - $now->getTimestamp()); ?>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span><span class="text-muted text-uppercase small"><?= esc($label) ?> :</span> <strong><?= esc($e['item_name']) ?></strong></span>
                            <span class="font-monospace small" data-effect-countdown data-seconds-left="<?= (int) $remaining ?>">
                                <?= $formatRemaining($remaining) ?>
                            </span>
                        </div>
                    <?php endif ?>
                </div></div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Addiction (compacte) -->
    <div class="card mb-3"><div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-baseline">
            <span class="small text-uppercase text-muted fw-semibold">Dépendance</span>
            <span class="font-monospace small"><?= $addiction ?> · <?= esc($tier['label']) ?></span>
        </div>
        <div class="progress mt-1" style="height: 4px;">
            <div class="progress-bar bg-dark" style="width: <?= min(100, $addiction) ?>%"></div>
        </div>
    </div></div>

    <!-- Filtres : icones categories en ligne -->
    <div class="d-flex flex-wrap gap-1 mb-3">
        <?php foreach ($categoryLabels as $key => $label): ?>
            <?php
                $isActive = $filter === $key;
                $count    = (int) ($counts[$key] ?? 0);
            ?>
            <a href="/inventory?cat=<?= esc($key) ?>"
               class="btn btn-sm <?= $isActive ? 'btn-dark' : 'btn-outline-dark' ?> d-flex align-items-center gap-1"
               title="<?= esc($label) ?>">
                <i class="bi <?= esc($categoryIcons[$key] ?? 'bi-box') ?>"></i>
                <span class="d-none d-md-inline"><?= esc($label) ?></span>
                <span class="badge bg-light text-dark border ms-1 font-monospace"><?= $count ?></span>
            </a>
        <?php endforeach ?>
    </div>

    <!-- Liste compacte -->
    <?php if (empty($rows)): ?>
        <p class="text-muted fst-italic small">Aucun objet pour ce filtre.</p>
    <?php else: ?>
        <div class="card">
            <ul class="list-group list-group-flush">
                <?php foreach ($rows as $r): ?>
                    <?php
                        $isEquipped   = (int) $r['equipped'] === 1;
                        $isConsumable = ! empty($r['consumable_type']);
                        $kind         = $r['consumable_type'] ?? null;

                        $cdRem = 0;
                        $hasActiveSameKind = false;
                        if ($isConsumable) {
                            $last = $kind === 'drug' ? $player['last_drug_at'] : $player['last_booster_at'];
                            $cdRem = $cooldownRemaining($last, (int) $r['cooldown_seconds']);
                            $hasActiveSameKind = $effectsByKind[$kind] !== null;
                        }

                        if (! empty($r['discontinued'])) {
                            $statusBadge = '<span class="badge bg-secondary">hors-circuit</span>';
                        } elseif ($isEquipped) {
                            $statusBadge = '<span class="badge bg-dark">équipé</span>';
                        } elseif ($isConsumable) {
                            $statusBadge = $cdRem > 0
                                ? '<span class="badge bg-light text-muted">cooldown ' . $formatRemaining($cdRem) . '</span>'
                                : ($hasActiveSameKind
                                    ? '<span class="badge bg-light text-muted">effet actif</span>'
                                    : '<span class="badge bg-light text-dark border">prêt</span>');
                        } else {
                            $statusBadge = '<span class="badge bg-light text-dark border">disponible</span>';
                        }

                        $basePrice = (int) ($r['price'] ?? 0);
                        $unitPay   = $basePrice > 0 ? (int) floor($basePrice * (int) $buyback_pct / 100) : 0;
                        $sellable  = ! $isEquipped && empty($r['discontinued']) && $unitPay > 0;
                        $listable  = ! $isEquipped && empty($r['discontinued']);
                    ?>
                    <li class="list-group-item p-0" x-data="{ open: false, sellOpen: false }">
                        <!-- Ligne principale compacte -->
                        <div class="d-flex align-items-center gap-2 px-3 py-2">
                            <i class="bi <?= esc($rowIcon($r)) ?> text-muted"></i>
                            <span class="fw-semibold flex-grow-1" style="cursor: pointer;" @click="open = !open">
                                <?= esc($r['name']) ?>
                            </span>
                            <span class="font-monospace small text-muted" title="Quantité">×<?= (int) $r['quantity'] ?></span>
                            <?= $statusBadge ?>

                            <!-- Action principale (consume / equip / unequip) -->
                            <?php if ($isConsumable): ?>
                                <form method="post" action="/inventory/consume/<?= (int) $r['pi_id'] ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-dark"
                                            <?= $cdRem > 0 || $hasActiveSameKind || ! empty($r['discontinued']) ? 'disabled' : '' ?>>
                                        <i class="bi bi-droplet"></i>
                                    </button>
                                </form>
                            <?php elseif ($isEquipped): ?>
                                <form method="post" action="/equipment/unequip/<?= esc($r['slot']) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Déséquiper">
                                        <i class="bi bi-arrow-down-circle"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/equipment/equip/<?= (int) $r['pi_id'] ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-dark"
                                            <?= ! empty($r['discontinued']) ? 'disabled' : '' ?> title="Équiper">
                                        <i class="bi bi-shield-check"></i>
                                    </button>
                                </form>
                            <?php endif ?>

                            <!-- Vendre au vendor PNJ : inline qty + bouton -->
                            <?php if ($sellable): ?>
                                <form method="post" action="/inventory/sell/<?= (int) $r['pi_id'] ?>" class="d-flex gap-1 m-0">
                                    <?= csrf_field() ?>
                                    <input type="number" name="quantity" min="1" max="<?= (int) $r['quantity'] ?>" value="1"
                                           class="form-control form-control-sm font-monospace text-center" style="width: 3.5rem;"
                                           title="Quantité à vendre">
                                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Vendre au vendor PNJ">
                                        <i class="bi bi-cash"></i> <?= number_format($unitPay) ?>¢/u
                                    </button>
                                </form>
                            <?php endif ?>

                            <!-- Bazaar (toggle expanded) -->
                            <?php if ($listable): ?>
                                <button type="button" class="btn btn-sm btn-outline-dark" @click="sellOpen = !sellOpen"
                                        title="Mettre sur le bazaar">
                                    <i class="bi bi-cash-coin"></i>
                                </button>
                            <?php endif ?>
                        </div>

                        <!-- Form bazaar (inline expandable) -->
                        <?php if ($listable): ?>
                            <form x-show="sellOpen" x-cloak method="post" action="/bazaar/list"
                                  class="row g-2 align-items-end px-3 pb-2 m-0 border-top bg-light pt-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="player_item_id" value="<?= (int) $r['pi_id'] ?>">
                                <input type="hidden" name="return_to" value="/inventory">
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Quantité</label>
                                    <input type="number" name="quantity" min="1" max="<?= (int) $r['quantity'] ?>" value="1"
                                           required class="form-control form-control-sm font-monospace">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Prix unitaire (¢)</label>
                                    <input type="number" name="unit_price" min="1" required
                                           class="form-control form-control-sm font-monospace" placeholder="ex: 5000">
                                </div>
                                <div class="col-md-5 d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-dark">Lister sur bazaar</button>
                                    <button type="button" class="btn btn-sm btn-light" @click="sellOpen = false">Annuler</button>
                                </div>
                            </form>
                        <?php endif ?>

                        <!-- Détail expandable -->
                        <div x-show="open" x-cloak class="px-3 pb-3 pt-1 border-top bg-light small">
                            <?php if (! empty($r['description'])): ?>
                                <p class="text-muted fst-italic mb-2"><?= esc($r['description']) ?></p>
                            <?php endif ?>
                            <div class="row g-2">
                                <?php if (! $isConsumable): ?>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Slot :</span> <?= esc(\App\Models\ItemModel::SLOTS[$r['slot']] ?? $r['slot']) ?></div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Bonus :</span> <?= $bonusInline($r) ?></div>
                                <?php else: ?>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Type :</span> <?= ucfirst($r['consumable_type']) ?></div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Cooldown :</span> <?= intdiv((int) $r['cooldown_seconds'], 60) ?> min</div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Durée :</span> <?= (int) $r['effect_duration_seconds'] > 0 ? intdiv((int) $r['effect_duration_seconds'], 60) . ' min' : 'instant' ?></div>
                                    <div class="col-md-12"><span class="text-muted text-uppercase">Effets :</span> <?= $effectsInline($r) ?></div>
                                    <?php if ($kind === 'drug'): ?>
                                        <div class="col-md-6"><span class="text-muted text-uppercase">Risque OD :</span> <?= (int) $r['overdose_chance_pct'] ?>%</div>
                                        <div class="col-md-6"><span class="text-muted text-uppercase">Addiction + :</span> <?= (int) $r['addiction_threshold_increase'] ?></div>
                                    <?php endif ?>
                                <?php endif ?>
                                <?php if ($basePrice > 0): ?>
                                    <div class="col-md-12 text-muted">
                                        Prix vendor : <?= number_format($basePrice) ?>¢
                                        · Rachat <?= (int) $buyback_pct ?>% : <?= number_format($unitPay) ?>¢/u
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

</div>

<script>
[x-cloak] { display: none !important; }
(function () {
    function fmt(s) { const m = Math.floor(s/60), r = s%60; return String(m).padStart(2,'0')+':'+String(r).padStart(2,'0'); }
    document.querySelectorAll('[data-effect-countdown]').forEach((el) => {
        let left = parseInt(el.dataset.secondsLeft, 10);
        const tick = () => { if (left <= 0) { window.location.reload(); return; } el.textContent = fmt(left); left--; };
        tick(); setInterval(tick, 1000);
    });
})();
</script>
<style>[x-cloak] { display: none !important; }</style>

<?= $this->endSection() ?>
