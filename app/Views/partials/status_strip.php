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

// Mini cellule barre + label + valeurs. Tout en string pour réutilisation locale.
$cell = static function (string $label, int $current, int $max, int $pct, string $colorClass): string {
    return '<div class="flex items-center gap-2 min-w-0">'
        . '<span class="' . $colorClass . ' font-bold uppercase tracking-wider text-[10px]">' . esc($label) . '</span>'
        . '<div class="w-20 md:w-24 h-1.5 bg-stone-200 rounded overflow-hidden">'
        .   '<div class="h-full ' . str_replace('text-', 'bg-', $colorClass) . ' transition-all" style="width: ' . $pct . '%"></div>'
        . '</div>'
        . '<span class="text-primary tabular-nums whitespace-nowrap">' . number_format($current) . ' / ' . number_format($max) . '</span>'
        . '</div>';
};
?>
<div class="bg-surface-alt border-b border-line">
    <div class="container mx-auto px-4 py-2 flex items-center gap-x-5 gap-y-2 text-xs flex-wrap">

        <?= $cell('HP',  (int) $player['hp_current'],     (int) $player['hp_max'],     $hpPct,     'text-hp') ?>
        <?= $cell('NRG', (int) $player['energy_current'], (int) $player['energy_max'], $energyPct, 'text-energy') ?>
        <?= $cell('NRV', (int) $player['nerve_current'],  (int) $player['nerve_max'],  $nervePct,  'text-nerve') ?>

        <span class="text-line">|</span>

        <div class="flex items-center gap-1">
            <span class="text-credits font-bold uppercase tracking-wider text-[10px]">¢</span>
            <span class="text-primary tabular-nums"><?= number_format($player['credits']) ?></span>
        </div>

        <span class="text-line">|</span>

        <div class="flex items-center gap-2 min-w-0">
            <span class="text-xp font-bold uppercase tracking-wider text-[10px]">Niv</span>
            <span class="text-primary tabular-nums"><?= (int) $player['level'] ?></span>
            <div class="w-16 md:w-20 h-1.5 bg-stone-200 rounded overflow-hidden">
                <div class="h-full bg-xp transition-all" style="width: <?= $xpPct ?>%"></div>
            </div>
            <span class="text-muted tabular-nums whitespace-nowrap text-[11px]">
                <?= number_format($player['xp']) ?> / <?= number_format($xpToNext) ?> XP
            </span>
        </div>

        <?php if (! empty($player['in_hospital_until'])): ?>
            <span class="ml-auto text-danger font-bold uppercase tracking-wider">[ Cyberclinique ]</span>
        <?php endif ?>

    </div>
</div>
