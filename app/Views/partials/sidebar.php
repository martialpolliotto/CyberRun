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


$inJail     = ! empty($player['in_jail_until'])     && \CodeIgniter\I18n\Time::parse($player['in_jail_until'])->isAfter($now);
$inHospital = ! empty($player['in_hospital_until']) && \CodeIgniter\I18n\Time::parse($player['in_hospital_until'])->isAfter($now);

$xpToNext = (int) $player['level'] * 100;

$unreadMessages = model(\App\Models\MessageModel::class)->unreadCount((int) $player['id']);

$factionsHref = ! empty($player['faction_id']) ? '/factions/mine' : '/factions';

// Compteur dailies claimables (= completed mais pas claimed pour aujourd'hui).
$claimableDailies = (int) db_connect()->table('daily_assignments')
    ->where('player_id', (int) $player['id'])
    ->where('day_date',  date('Y-m-d'))
    ->where('completed_at IS NOT NULL', null, false)
    ->where('claimed_at IS NULL',       null, false)
    ->countAllResults();

// Amis online (badge sidebar Relations).
$onlineThreshold = (int) model(\App\Models\GameSettingModel::class)->get('online_threshold_seconds', 300);
$onlineFriends   = model(\App\Models\PlayerRelationModel::class)->countOnlineFriends((int) $player['id'], $onlineThreshold);

$navItems = [
    ['Profil',      '/profile',      'bi-person',          null,                                              'profile'],
    ['Messages',    '/messages',     'bi-envelope',        $unreadMessages > 0 ? $unreadMessages : null,      null],
    ['Dailies',     '/dailies',      'bi-calendar-check',  $claimableDailies > 0 ? $claimableDailies : null,  'dailies'],
    ['Log',         '/log',          'bi-clock-history',   null,                                              null],
    ['Crimes',      '/crimes',       'bi-mask',            null,                                              'crimes'],
    ['Lab',         '/lab',          'bi-flask',           null,                                              'lab'],
    ['Chrome City', '/city',         'bi-building',        null,                                              null],
    ['Jobs',        '/jobs',         'bi-briefcase',       null,                                              'jobs'],
    ['Faction',     $factionsHref,   'bi-shield-fill',     null,                                              'faction'],
    ['Guerres',     '/factions/wars','bi-fire',            null,                                              null],
    ['Équipement',  '/equipment',    'bi-shield',          null,                                              null],
    ['Inventaire',  '/inventory',    'bi-bag',             null,                                              null],
    ['Bazaar',      '/bazaar/mine',  'bi-cash-coin',       null,                                              'bazaar'],
    ['Joueurs',     '/players',      'bi-people',          null,                                              null],
    ['Relations',   '/relations',    'bi-person-heart',    $onlineFriends > 0 ? $onlineFriends : null,        null],
    ['Classements', '/leaderboards', 'bi-trophy',          null,                                              null],
    ['Wiki',        '/wiki',         'bi-book',            null,                                              'wiki'],
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

        <!-- Identite : pseudo (statique) + bloc dynamique solde/niveau/streak en partial OOB-able -->
        <div class="small mb-2">
            <div class="d-flex">
                <span class="text-muted" style="width: 5rem;">Pseudo</span>
                <a href="/profile" class="text-dark text-decoration-none fw-bold"><?= esc($user->username) ?></a>
            </div>
        </div>
        <?= view('partials/_identity_stats', ['player' => $player]) ?>

        <!-- Jauges ressources : partial reutilise comme cible OOB pour les actions HTMX. -->
        <?= view('partials/_resources', ['player' => $player, 'xpToNext' => $xpToNext]) ?>

        <!-- Navigation principale -->
        <nav class="mt-3 small">
            <ul class="list-unstyled mb-0">
                <?php foreach ($navItems as $nav): ?>
                    <?php [$label, $href, $icon, $badge, $tour] = array_pad($nav, 5, null); ?>
                    <li>
                        <a href="<?= esc($href) ?>" class="d-flex align-items-center gap-2 py-1 text-dark text-decoration-none"
                           <?= $tour ? 'data-tour="' . esc($tour, 'attr') . '"' : '' ?>>
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

<script>
(function () {
    // Countdown live pour chaque jauge ressource. Idempotent + re-init apres swap HTMX
    // (notamment OOB sur #cr-resources qui replace le DOM des jauges sans reload page).
    const initTimer = (el) => {
        if (el.dataset.crTimerInit === '1') return;
        el.dataset.crTimerInit = '1';
        const initialLeft = parseInt(el.dataset.secondsLeft, 10);
        if (! initialLeft || initialLeft <= 0) { el.textContent = 'FULL'; return; }
        const startMs = Date.now();
        let intervalId;
        const tick = () => {
            const elapsed = Math.floor((Date.now() - startMs) / 1000);
            const left = initialLeft - elapsed;
            if (left <= 0) {
                el.textContent = 'FULL';
                clearInterval(intervalId);
                return;
            }
            const m = Math.floor(left / 60), s = left % 60;
            el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        };
        tick();
        intervalId = setInterval(tick, 1000);
    };

    const initAll = () => document.querySelectorAll('[data-cr-resource-timer]').forEach(initTimer);

    initAll();
    document.body.addEventListener('htmx:afterSettle', initAll);
})();
</script>
