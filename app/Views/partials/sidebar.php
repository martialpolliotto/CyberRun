<?php
/**
 * Sidebar gauche permanente, style Torn. Affiche identite + status icons + ressources
 * + solde + nav. Affichee uniquement si user logged + fiche player existe.
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
$now = \CodeIgniter\I18n\Time::now();

// Calcule les minutes restantes jusqu'au max (regen 1 NRV/min, 2 NRG/min, 5 Life/min via TickCommand).
$timeUntilFull = static function (int $current, int $max, int $regenPerTick): string {
    if ($current >= $max) return 'FULL';
    if ($regenPerTick <= 0) return '';
    $minutes = (int) ceil(($max - $current) / $regenPerTick);
    if ($minutes < 60) return $minutes . 'm';
    $h = intdiv($minutes, 60); $m = $minutes % 60;
    return $h . 'h' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
};

$inJail     = ! empty($player['in_jail_until'])     && \CodeIgniter\I18n\Time::parse($player['in_jail_until'])->isAfter($now);
$inHospital = ! empty($player['in_hospital_until']) && \CodeIgniter\I18n\Time::parse($player['in_hospital_until'])->isAfter($now);

$hpPct  = (int) round(((int) $player['hp_current']     / max(1, (int) $player['hp_max']))     * 100);
$nrgPct = (int) round(((int) $player['energy_current'] / max(1, (int) $player['energy_max'])) * 100);
$nrvPct = (int) round(((int) $player['nerve_current']  / max(1, (int) $player['nerve_max']))  * 100);

$xpToNext = (int) $player['level'] * 100;
$xpPct    = (int) round(((int) $player['xp'] / max(1, $xpToNext)) * 100);

$unreadMessages = model(\App\Models\MessageModel::class)->unreadCount((int) $player['id']);

$factionsHref = ! empty($player['faction_id']) ? '/factions/mine' : '/factions';

$navItems = [
    ['Profil',      '/profile',      'bi-person',          null],
    ['Messages',    '/messages',     'bi-envelope',        $unreadMessages > 0 ? $unreadMessages : null],
    ['Log',         '/log',          'bi-clock-history',   null],
    ['Chrome City', '/city',         'bi-building',        null],
    ['Jobs',        '/jobs',         'bi-briefcase',       null],
    ['Faction',     $factionsHref,   'bi-shield-fill',     null],
    ['Équipement',  '/equipment',    'bi-shield',          null],
    ['Inventaire',  '/inventory',    'bi-bag',             null],
    ['Joueurs',     '/players',      'bi-people',          null],
    ['Classements', '/leaderboards', 'bi-trophy',          null],
];
?>
<aside class="cr-sidebar bg-white border-end" style="width: 280px; flex-shrink: 0;">
    <div class="p-3">

        <!-- Header bloc -->
        <div class="border-bottom pb-2 mb-2 small text-uppercase fw-semibold text-muted">Information</div>

        <!-- Ligne d'icones status (conditionnelles) -->
        <div class="d-flex flex-wrap gap-2 mb-3 small">
            <?php if ($player['sex'] === 'm'): ?>
                <span class="text-dark" title="Homme"><i class="bi bi-gender-male"></i></span>
            <?php elseif ($player['sex'] === 'f'): ?>
                <span class="text-dark" title="Femme"><i class="bi bi-gender-female"></i></span>
            <?php endif ?>
            <?php if (! empty($player['job'])): ?>
                <span class="text-dark" title="Job : <?= esc($player['job']) ?>"><i class="bi bi-briefcase"></i></span>
            <?php endif ?>
            <?php if (! empty($player['married_to_player_id'])): ?>
                <span class="text-dark" title="Marié"><i class="bi bi-heart-fill"></i></span>
            <?php endif ?>
            <?php if (! empty($player['faction_id'])): ?>
                <span class="text-dark" title="Faction"><i class="bi bi-shield-fill"></i></span>
            <?php endif ?>
            <?php if ((int) $player['is_donator'] === 1): ?>
                <span class="text-dark" title="Donator"><i class="bi bi-star-fill"></i></span>
            <?php endif ?>
            <?php if ($inJail): ?>
                <a href="/jail" class="text-dark" title="En prison"><i class="bi bi-lock-fill"></i></a>
            <?php endif ?>
            <?php if ($inHospital): ?>
                <a href="/profile" class="text-dark" title="Cyberclinique"><i class="bi bi-bandaid-fill"></i></a>
            <?php endif ?>
        </div>

        <!-- Identité + solde -->
        <div class="small mb-3">
            <div class="d-flex">
                <span class="text-muted" style="width: 5rem;">Pseudo</span>
                <a href="/profile" class="text-dark text-decoration-none fw-bold"><?= esc($user->username) ?></a>
            </div>
            <div class="d-flex">
                <span class="text-muted" style="width: 5rem;">Solde</span>
                <span class="fw-bold font-monospace">¢<?= number_format((int) $player['credits']) ?></span>
            </div>
            <div class="d-flex">
                <span class="text-muted" style="width: 5rem;">Niveau</span>
                <span class="fw-bold font-monospace"><?= (int) $player['level'] ?></span>
            </div>
        </div>

        <!-- Jauges ressources -->
        <div class="small mb-3">
            <?php
                $bars = [
                    ['Life',  (int) $player['hp_current'],     (int) $player['hp_max'],     $hpPct,  $timeUntilFull((int) $player['hp_current'],     (int) $player['hp_max'],     5)],
                    ['NRG',   (int) $player['energy_current'], (int) $player['energy_max'], $nrgPct, $timeUntilFull((int) $player['energy_current'], (int) $player['energy_max'], 2)],
                    ['NRV',   (int) $player['nerve_current'],  (int) $player['nerve_max'],  $nrvPct, $timeUntilFull((int) $player['nerve_current'],  (int) $player['nerve_max'],  1)],
                ];
                foreach ($bars as [$label, $cur, $max, $pct, $until]):
            ?>
                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="fw-semibold"><?= esc($label) ?></span>
                    <span class="text-muted font-monospace"><?= number_format($cur) ?>/<?= number_format($max) ?></span>
                </div>
                <div class="progress mb-1" style="height: 4px;">
                    <div class="progress-bar bg-dark" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="text-end text-muted font-monospace small mb-2" style="margin-top: -3px;"><?= esc($until) ?></div>
            <?php endforeach ?>
        </div>

        <!-- XP barre (sera cachee a terme — note en memoire) -->
        <div class="small mb-3">
            <div class="d-flex justify-content-between align-items-baseline">
                <span class="fw-semibold">XP</span>
                <span class="text-muted font-monospace"><?= number_format((int) $player['xp']) ?>/<?= number_format($xpToNext) ?></span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-dark" style="width: <?= $xpPct ?>%"></div>
            </div>
        </div>

        <!-- Navigation principale -->
        <nav class="mt-3 small">
            <ul class="list-unstyled mb-0">
                <?php foreach ($navItems as [$label, $href, $icon, $badge]): ?>
                    <li>
                        <a href="<?= esc($href) ?>" class="d-flex align-items-center gap-2 py-1 text-dark text-decoration-none">
                            <i class="bi <?= esc($icon) ?>" style="width: 1.2rem;"></i>
                            <span class="flex-grow-1"><?= esc($label) ?></span>
                            <?php if ($badge !== null): ?>
                                <span class="badge bg-dark"><?= (int) $badge ?></span>
                            <?php endif ?>
                        </a>
                    </li>
                <?php endforeach ?>
                <?php if (auth()->user()->inGroup('admin', 'superadmin')): ?>
                    <li>
                        <a href="/admin" class="d-flex align-items-center gap-2 py-1 text-dark text-decoration-none fw-bold">
                            <i class="bi bi-gear" style="width: 1.2rem;"></i>
                            Admin
                        </a>
                    </li>
                <?php endif ?>
                <li class="border-top mt-2 pt-2">
                    <a href="/logout" class="d-flex align-items-center gap-2 py-1 text-muted text-decoration-none">
                        <i class="bi bi-box-arrow-right" style="width: 1.2rem;"></i>
                        Déconnexion
                    </a>
                </li>
            </ul>
        </nav>

    </div>
</aside>
