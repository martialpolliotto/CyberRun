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

    $boosters = array_values(array_filter($consumables, static fn($c) => $c['consumable_type'] === 'booster'));
    $drugs    = array_values(array_filter($consumables, static fn($c) => $c['consumable_type'] === 'drug'));

    // Niveau d'addiction + tier resolu (avec ses penalites actives).
    $addiction     = (int) $player['addiction_level'];
    $tier          = \App\Models\PlayerModel::addictionTier($addiction);
    $addictionTier = (string) $tier['label'];

    // Helper d'affichage des effets d'un item en ligne.
    $effectsLine = static function (array $c): string {
        $parts = [];
        if ((int) $c['effect_hp']  > 0) $parts[] = '+' . (int) $c['effect_hp']  . ' HP';
        if ((int) $c['effect_nrg'] > 0) $parts[] = '+' . (int) $c['effect_nrg'] . ' NRG';
        if ((int) $c['effect_nrv'] > 0) $parts[] = '+' . (int) $c['effect_nrv'] . ' NRV';
        foreach (['force', 'blindage', 'reflexes', 'hack'] as $stat) {
            if ((int) $c['effect_' . $stat] > 0) $parts[] = '+' . (int) $c['effect_' . $stat] . ' ' . ucfirst($stat);
        }
        foreach (['hp_max' => 'HP max', 'nrg_max' => 'NRG max', 'nrv_max' => 'NRV max'] as $col => $label) {
            if ((int) $c['effect_' . $col] > 0) $parts[] = '+' . (int) $c['effect_' . $col] . ' ' . $label;
        }
        return $parts === [] ? '—' : implode(', ', $parts);
    };

    $formatRemaining = static function (int $seconds): string {
        if ($seconds <= 0) return '';
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
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
    <div class="row g-3 mb-4">
        <?php foreach (['booster' => 'Booster actif', 'drug' => 'Drogue active'] as $kind => $label): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light small text-uppercase fw-semibold"><?= esc($label) ?></div>
                    <div class="card-body">
                        <?php $e = $effectsByKind[$kind] ?? null; ?>
                        <?php if ($e === null): ?>
                            <p class="text-muted fst-italic mb-0">Aucun.</p>
                        <?php else: ?>
                            <?php $remaining = max(0, \CodeIgniter\I18n\Time::parse($e['expires_at'])->getTimestamp() - $now->getTimestamp()); ?>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <strong><?= esc($e['item_name']) ?></strong>
                                <span class="font-monospace small"
                                      data-effect-countdown
                                      data-seconds-left="<?= (int) $remaining ?>">
                                    <?= $formatRemaining($remaining) ?>
                                </span>
                            </div>
                            <div class="small text-muted mt-1">
                                <?php
                                    $bits = [];
                                    foreach (['effect_force' => 'Force', 'effect_blindage' => 'Blindage', 'effect_reflexes' => 'Réflexes', 'effect_hack' => 'Hack'] as $col => $lbl) {
                                        if ((int) $e[$col] > 0) $bits[] = '+' . (int) $e[$col] . ' ' . $lbl;
                                    }
                                    foreach (['effect_hp_max' => 'HP max', 'effect_nrg_max' => 'NRG max', 'effect_nrv_max' => 'NRV max'] as $col => $lbl) {
                                        if ((int) $e[$col] > 0) $bits[] = '+' . (int) $e[$col] . ' ' . $lbl;
                                    }
                                    echo $bits === [] ? '—' : esc(implode(' · ', $bits));
                                ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Addiction -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline mb-1">
                <span class="small text-uppercase text-muted fw-semibold">Dépendance</span>
                <span class="font-monospace"><?= $addiction ?> · <?= esc($addictionTier) ?></span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-dark" style="width: <?= min(100, $addiction) ?>%"></div>
            </div>

            <?php if ((int) $tier['stat_malus'] > 0 || (int) $tier['overdose_bonus'] > 0): ?>
                <ul class="list-unstyled small mt-3 mb-0">
                    <?php if ((int) $tier['stat_malus'] > 0): ?>
                        <li>Pénalité : <strong>−<?= (int) $tier['stat_malus'] ?></strong> sur chaque stat effective (Force, Blindage, Réflexes, Hack).</li>
                    <?php endif ?>
                    <?php if ((int) $tier['overdose_bonus'] > 0): ?>
                        <li>Risque d'overdose : <strong>+<?= (int) $tier['overdose_bonus'] ?>%</strong> sur la prochaine drogue.</li>
                    <?php endif ?>
                </ul>
            <?php else: ?>
                <p class="form-text mt-2 mb-0">
                    Paliers : 25 = éveillé · 50 = accro (−2 stats / +5% overdose) ·
                    75 = dépendant (−5 / +10%) · 100 = sevrage (−10 / +20%).
                    Décay : <?= (int) \App\Models\PlayerModel::ADDICTION_DAILY_DECAY ?> points / jour.
                </p>
            <?php endif ?>
        </div>
    </div>

    <!-- Liste des consommables -->
    <?php foreach (['Boosters' => $boosters, 'Drogues' => $drugs] as $sectionLabel => $items): ?>
        <h2 class="small text-uppercase text-muted fw-semibold mt-3 mb-2"><?= esc($sectionLabel) ?> (<?= count($items) ?>)</h2>
        <?php if (empty($items)): ?>
            <p class="text-muted fst-italic small">Aucun en stock.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle bg-white small">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Effets</th>
                            <th>Durée</th>
                            <th>Cooldown</th>
                            <th>Risque</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $c): ?>
                            <?php
                                $kind  = $c['consumable_type'];
                                $last  = $kind === 'drug' ? $player['last_drug_at'] : $player['last_booster_at'];
                                $cdRem = $cooldownRemaining($last, (int) $c['cooldown_seconds']);
                                $hasActive = $effectsByKind[$kind] !== null;
                                $disabled  = $cdRem > 0 || $hasActive;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= esc($c['item_name']) ?></strong>
                                    <?php if (! empty($c['item_description'])): ?>
                                        <div class="text-muted fst-italic small"><?= esc($c['item_description']) ?></div>
                                    <?php endif ?>
                                </td>
                                <td><?= esc($effectsLine($c)) ?></td>
                                <td>
                                    <?= (int) $c['effect_duration_seconds'] > 0
                                        ? '<span class="font-monospace">' . intdiv((int) $c['effect_duration_seconds'], 60) . ' min</span>'
                                        : '<span class="text-muted">instant</span>' ?>
                                </td>
                                <td>
                                    <?php if ($cdRem > 0): ?>
                                        <span class="font-monospace text-muted"
                                              data-cd-countdown
                                              data-seconds-left="<?= (int) $cdRem ?>">
                                            <?= $formatRemaining($cdRem) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="font-monospace">prêt</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php if ($kind === 'drug'): ?>
                                        <div class="small">Overdose <?= (int) $c['overdose_chance_pct'] ?>%</div>
                                        <div class="small text-muted">Addict. +<?= (int) $c['addiction_threshold_increase'] ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end">
                                    <form method="post" action="/inventory/consume/<?= (int) $c['id'] ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                <?= $disabled ? 'disabled' : '' ?>
                                                class="btn btn-sm btn-dark">
                                            <?= $hasActive ? 'effet actif' : ($cdRem > 0 ? 'cooldown' : 'Consommer') ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    <?php endforeach ?>

</div>

<script>
(function () {
    function fmt(s) {
        const m = Math.floor(s / 60), r = s % 60;
        return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
    }
    document.querySelectorAll('[data-effect-countdown], [data-cd-countdown]').forEach((el) => {
        let left = parseInt(el.dataset.secondsLeft, 10);
        const tick = () => {
            if (left <= 0) { window.location.reload(); return; }
            el.textContent = fmt(left);
            left--;
        };
        tick();
        setInterval(tick, 1000);
    });
})();
</script>

<?= $this->endSection() ?>
