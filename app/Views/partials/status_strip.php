<?php
/**
 * Bandeau persistant sous le header : HP/Énergie/Nerve + crédits + niveau.
 * S'affiche uniquement si user connecté + fiche player existe.
 *
 * Fait UNE requête BDD par page rendue (player). Acceptable en MVP, à cacher
 * si on monte en charge (cache 1-5s sur la fiche player).
 */

if (! function_exists('auth') || ! auth()->loggedIn()) {
    return;
}

$user   = auth()->user();
$player = model(\App\Models\PlayerModel::class)->findByUserId((int) $user->id);
if ($player === null) {
    return;
}

helper('number');

$hpPct     = (int) round(($player['hp_current']     / max(1, $player['hp_max']))     * 100);
$energyPct = (int) round(($player['energy_current'] / max(1, $player['energy_max'])) * 100);
$nervePct  = (int) round(($player['nerve_current']  / max(1, $player['nerve_max']))  * 100);

$xpToNext  = $player['level'] * 100;
$xpPct     = (int) round(($player['xp'] / max(1, $xpToNext)) * 100);

$cell = static function (string $label, int $current, int $max, int $pct): string {
    return '<div class="d-flex align-items-center gap-2">'
        . '<span class="fw-bold text-uppercase small">' . esc($label) . '</span>'
        . '<div class="progress" style="width: 80px; height: 6px;">'
        .   '<div class="progress-bar bg-dark" style="width: ' . $pct . '%"></div>'
        . '</div>'
        . '<span class="font-monospace small">' . number_format($current) . ' / ' . number_format($max) . '</span>'
        . '</div>';
};
?>
<div class="bg-light border-bottom">
    <div class="container py-2 d-flex align-items-center flex-wrap gap-3 small">

        <?= $cell('Life', (int) $player['hp_current'],     (int) $player['hp_max'],     $hpPct) ?>
        <?= $cell('NRG', (int) $player['energy_current'], (int) $player['energy_max'], $energyPct) ?>
        <?= $cell('NRV', (int) $player['nerve_current'],  (int) $player['nerve_max'],  $nervePct) ?>

        <span class="text-muted">|</span>

        <div class="d-flex align-items-center gap-1">
            <span class="fw-bold text-uppercase small">¢</span>
            <span class="font-monospace"><?= number_format($player['credits']) ?></span>
        </div>

        <span class="text-muted">|</span>

        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-uppercase small">Niv</span>
            <span class="font-monospace"><?= (int) $player['level'] ?></span>
            <div class="progress" style="width: 64px; height: 6px;">
                <div class="progress-bar bg-dark" style="width: <?= $xpPct ?>%"></div>
            </div>
            <span class="text-muted font-monospace small">
                <?= number_format($player['xp']) ?> / <?= number_format($xpToNext) ?> XP
            </span>
        </div>

        <?php
            $statuses = [];
            $now = \CodeIgniter\I18n\Time::now();
            if (! empty($player['in_hospital_until'])) {
                $until = \CodeIgniter\I18n\Time::parse($player['in_hospital_until']);
                if ($until->isAfter($now)) {
                    $mins = max(0, (int) ceil(($until->getTimestamp() - $now->getTimestamp()) / 60));
                    $statuses[] = '<a href="/profile" class="text-dark text-decoration-none fw-bold text-uppercase">[ Cyberclinique ' . $mins . 'm ]</a>';
                }
            }
            if (! empty($player['in_jail_until'])) {
                $until = \CodeIgniter\I18n\Time::parse($player['in_jail_until']);
                if ($until->isAfter($now)) {
                    $mins = max(0, (int) ceil(($until->getTimestamp() - $now->getTimestamp()) / 60));
                    $statuses[] = '<a href="/jail" class="text-dark text-decoration-none fw-bold text-uppercase">[ Prison ' . $mins . 'm ]</a>';
                }
            }
        ?>
        <?php if ($statuses !== []): ?>
            <span class="ms-auto d-flex gap-2"><?= implode(' ', $statuses) ?></span>
        <?php endif ?>

    </div>
</div>
