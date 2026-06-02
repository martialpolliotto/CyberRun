<?php
/**
 * Bloc des 4 jauges ressources (Life / Energy / Nerve / Xp) inclus dans la sidebar.
 *
 * Sert aussi de cible OOB pour les actions HTMX qui modifient les ressources sans
 * recharger la page : la reponse du serveur peut inclure ce partial avec
 * hx-swap-oob="true" pour rafraichir les jauges en meme temps que le contenu principal.
 *
 * Params attendus :
 *   - $player    : fiche player (array)
 *   - $xpToNext  : palier XP du prochain niveau
 *   - $oob       : bool, true = ajoute l'attribut hx-swap-oob="true"
 */

$hp = (int) $player['hp_current'];     $hpMax  = (int) $player['hp_max'];
$en = (int) $player['energy_current']; $enMax  = (int) $player['energy_max'];
$nv = (int) $player['nerve_current'];  $nvMax  = (int) $player['nerve_max'];
$xp = (int) $player['xp'];

$hpPct = (int) round(($hp / max(1, $hpMax)) * 100);
$enPct = (int) round(($en / max(1, $enMax)) * 100);
$nvPct = (int) round(($nv / max(1, $nvMax)) * 100);
$xpPct = (int) round(($xp / max(1, $xpToNext)) * 100);

$secondsUntilFull = static function (int $current, int $max, int $regenPerTick): int {
    if ($current >= $max || $regenPerTick <= 0) return 0;
    return (int) ceil(($max - $current) / $regenPerTick) * 60;
};

$bars = [
    ['Life',   $hp, $hpMax,    $hpPct, $secondsUntilFull($hp, $hpMax, 5), 'life'],
    ['Energy', $en, $enMax,    $enPct, $secondsUntilFull($en, $enMax, 2), 'energy'],
    ['Nerve',  $nv, $nvMax,    $nvPct, $secondsUntilFull($nv, $nvMax, 1), 'nerve'],
    ['Xp',     $xp, $xpToNext, $xpPct, null,                              'xp'],
];
?>
<div id="cr-resources" class="small mb-3"<?= !empty($oob) ? ' hx-swap-oob="true"' : '' ?>>
    <?php foreach ($bars as [$label, $cur, $max, $pct, $seconds, $color]): ?>
        <div class="d-flex justify-content-between align-items-baseline">
            <span class="fw-semibold"><?= esc($label) ?>:</span>
            <span class="font-monospace">
                <span class="text-muted"><?= number_format($cur) ?>/<?= number_format($max) ?></span>
                <?php if ($seconds !== null): ?>
                    <span class="text-muted ms-2" data-cr-resource-timer data-seconds-left="<?= (int) $seconds ?>">
                        <?= $seconds === 0 ? 'FULL' : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60) ?>
                    </span>
                <?php else: ?>
                    <span class="text-muted ms-2">Niv <?= (int) $player['level'] + 1 ?></span>
                <?php endif ?>
            </span>
        </div>
        <div class="progress cr-bar-notched mb-2" style="height: 6px;">
            <div class="progress-bar cr-bar-<?= esc($color, 'attr') ?>" style="width: <?= $pct ?>%"></div>
        </div>
    <?php endforeach ?>
</div>
