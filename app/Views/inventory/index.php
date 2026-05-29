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
    $categoryBadge = static function (string $cat) use ($categoryLabels): string {
        return '<span class="badge bg-light text-dark border">' . esc($categoryLabels[$cat] ?? $cat) . '</span>';
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

    <!-- Effets actifs -->
    <div class="row g-3 mb-3">
        <?php foreach (['booster' => 'Booster actif', 'drug' => 'Drogue active'] as $kind => $label): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light small text-uppercase fw-semibold"><?= esc($label) ?></div>
                    <div class="card-body py-2">
                        <?php $e = $effectsByKind[$kind] ?? null; ?>
                        <?php if ($e === null): ?>
                            <span class="text-muted fst-italic small">Aucun.</span>
                        <?php else: ?>
                            <?php $remaining = max(0, \CodeIgniter\I18n\Time::parse($e['expires_at'])->getTimestamp() - $now->getTimestamp()); ?>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <strong><?= esc($e['item_name']) ?></strong>
                                <span class="font-monospace small" data-effect-countdown data-seconds-left="<?= (int) $remaining ?>">
                                    <?= $formatRemaining($remaining) ?>
                                </span>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Addiction (condensee si rien d'actif) -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-baseline">
                <span class="small text-uppercase text-muted fw-semibold">Dépendance</span>
                <span class="font-monospace small"><?= $addiction ?> · <?= esc($tier['label']) ?></span>
            </div>
            <div class="progress mt-1" style="height: 4px;">
                <div class="progress-bar bg-dark" style="width: <?= min(100, $addiction) ?>%"></div>
            </div>
            <?php if ((int) $tier['stat_malus'] > 0 || (int) $tier['overdose_bonus'] > 0): ?>
                <div class="small text-muted mt-1">
                    <?php if ((int) $tier['stat_malus'] > 0): ?>−<?= (int) $tier['stat_malus'] ?> stats · <?php endif ?>
                    <?php if ((int) $tier['overdose_bonus'] > 0): ?>+<?= (int) $tier['overdose_bonus'] ?>% overdose<?php endif ?>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Filtres -->
    <form method="get" action="/inventory" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <select name="cat" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($categoryLabels as $k => $label): ?>
                        <option value="<?= esc($k) ?>" <?= $filter === $k ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-8 d-flex align-items-center text-muted small">
                <?= count($rows) ?> objet<?= count($rows) > 1 ? 's' : '' ?> affiché<?= count($rows) > 1 ? 's' : '' ?>
                <?php if ($filter !== 'all'): ?>
                    sur <?= (int) $totalCount ?> au total
                <?php endif ?>
            </div>
        </div>
    </form>

    <!-- Liste expandable -->
    <?php if (empty($rows)): ?>
        <p class="text-muted fst-italic small">Aucun objet pour ce filtre.</p>
    <?php else: ?>
        <div class="card">
            <ul class="list-group list-group-flush">
                <?php foreach ($rows as $r): ?>
                    <?php
                        $isEquipped  = (int) $r['equipped'] === 1;
                        $isConsumable = ! empty($r['consumable_type']);
                        $kind = $r['consumable_type'] ?? null;

                        $cdRem = 0;
                        $hasActiveSameKind = false;
                        if ($isConsumable) {
                            $last = $kind === 'drug' ? $player['last_drug_at'] : $player['last_booster_at'];
                            $cdRem = $cooldownRemaining($last, (int) $r['cooldown_seconds']);
                            $hasActiveSameKind = $effectsByKind[$kind] !== null;
                        }

                        // Status badge.
                        if (! empty($r['discontinued'])) {
                            $statusBadge = '<span class="badge bg-secondary">hors-circuit</span>';
                        } elseif ($isEquipped) {
                            $statusBadge = '<span class="badge bg-dark">équipé</span>';
                        } elseif ($isConsumable) {
                            $statusBadge = $cdRem > 0
                                ? '<span class="badge bg-light text-muted">cooldown ' . $formatRemaining($cdRem) . '</span>'
                                : ($hasActiveSameKind
                                    ? '<span class="badge bg-light text-muted">effet déjà actif</span>'
                                    : '<span class="badge bg-light text-dark border">prêt</span>');
                        } else {
                            $statusBadge = '<span class="badge bg-light text-dark border">disponible</span>';
                        }
                    ?>
                    <li class="list-group-item p-0" x-data="{ open: false }">
                        <!-- Ligne compacte cliquable -->
                        <div class="d-flex align-items-center gap-3 px-3 py-2 user-select-none" style="cursor: pointer;" @click="open = !open">
                            <span class="text-muted small font-monospace" style="width: 1rem;" x-text="open ? '−' : '+'">+</span>
                            <strong class="flex-grow-1"><?= esc($r['name']) ?></strong>
                            <?= $categoryBadge($r['_category']) ?>
                            <?= $statusBadge ?>
                        </div>
                        <!-- Détail dépliable -->
                        <div x-show="open" x-cloak class="px-3 pb-3 pt-1 border-top bg-light">
                            <?php if (! empty($r['description'])): ?>
                                <p class="text-muted fst-italic small mb-2"><?= esc($r['description']) ?></p>
                            <?php endif ?>

                            <div class="row g-2 small mb-2">
                                <?php if (! $isConsumable): ?>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Slot :</span> <?= esc(\App\Models\ItemModel::SLOTS[$r['slot']] ?? $r['slot']) ?></div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Bonus :</span> <?= $bonusInline($r) ?></div>
                                <?php else: ?>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Type :</span> <?= ucfirst($r['consumable_type']) ?></div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Cooldown :</span> <?= intdiv((int) $r['cooldown_seconds'], 60) ?> min</div>
                                    <div class="col-md-4"><span class="text-muted text-uppercase">Durée effet :</span> <?= (int) $r['effect_duration_seconds'] > 0 ? intdiv((int) $r['effect_duration_seconds'], 60) . ' min' : 'instant' ?></div>
                                    <div class="col-md-12"><span class="text-muted text-uppercase">Effets :</span> <?= $effectsInline($r) ?></div>
                                    <?php if ($kind === 'drug'): ?>
                                        <div class="col-md-6"><span class="text-muted text-uppercase">Risque overdose :</span> <?= (int) $r['overdose_chance_pct'] ?>%</div>
                                        <div class="col-md-6"><span class="text-muted text-uppercase">Addiction +</span> <?= (int) $r['addiction_threshold_increase'] ?></div>
                                    <?php endif ?>
                                <?php endif ?>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($isConsumable): ?>
                                    <form method="post" action="/inventory/consume/<?= (int) $r['pi_id'] ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-dark"
                                                <?= $cdRem > 0 || $hasActiveSameKind || ! empty($r['discontinued']) ? 'disabled' : '' ?>>
                                            <?php if ($cdRem > 0): ?>
                                                Cooldown <?= $formatRemaining($cdRem) ?>
                                            <?php elseif ($hasActiveSameKind): ?>
                                                Effet déjà actif
                                            <?php else: ?>
                                                Consommer
                                            <?php endif ?>
                                        </button>
                                    </form>
                                <?php elseif ($isEquipped): ?>
                                    <form method="post" action="/equipment/unequip/<?= esc($r['slot']) ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">Déséquiper</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/equipment/equip/<?= (int) $r['pi_id'] ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-dark"
                                                <?= ! empty($r['discontinued']) ? 'disabled' : '' ?>>
                                            Équiper
                                        </button>
                                    </form>
                                <?php endif ?>
                                <a href="/equipment" class="btn btn-sm btn-link text-muted text-decoration-none">voir slots</a>
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
