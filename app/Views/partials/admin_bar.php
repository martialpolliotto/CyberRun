<?php
/**
 * Barre admin flottante en bas de l'ecran (uniquement pour les groupes admin/superadmin).
 *
 * Quick-actions pour playtest : ajuster ressources/XP/credits + forcer prison/hopital.
 * Toutes les actions sont HTMX : POST sur /admin/player-tools/adjust|state, reponse =
 * partial _resources en OOB swap (rafraichit les jauges sidebar sans reload).
 *
 * Position : fixed bottom-left, n'interfere pas avec le chat widget (bottom-right).
 * Repliable via toggle persiste en localStorage.
 */

if (! function_exists('auth') || ! auth()->loggedIn()) return;
if (! auth()->user()->inGroup('admin', 'superadmin')) return;

$csrfTokenHeader = '{"X-CSRF-TOKEN":"' . csrf_hash() . '"}';

// Groupes de boutons : [label, field, [deltas]]
$resourceFields = [
    ['Nerve',  'nerve_current',  [-100, -10, 10, 100]],
    ['Energy', 'energy_current', [-100, -10, 10, 100]],
    ['Life',   'hp_current',     [-100, -10, 10, 100]],
];
$progressionFields = [
    ['XP',  'xp',      [100, 1000]],
    ['¢',   'credits', [10000, 100000]],
];
?>

<style>
.cr-admin-bar {
    position: fixed;
    bottom: 0; left: 1rem; z-index: 1048;
    background: #1a1a1a; color: #fff;
    border: 1px solid #444; border-radius: 6px 6px 0 0;
    font-size: 0.95rem; max-width: calc(100vw - 5rem);
    box-shadow: 0 -2px 8px rgba(0,0,0,0.4);
}
.cr-admin-bar.collapsed .cr-admin-bar-body { display: none; }
.cr-admin-bar-toggle {
    background: #c10000; color: #fff; border: none;
    padding: 0.35rem 0.9rem; font-size: 0.9rem; font-weight: bold;
    text-transform: uppercase; cursor: pointer; letter-spacing: 0.5px;
    border-radius: 6px 6px 0 0; width: 100%; text-align: left;
}
.cr-admin-bar-body { padding: 0.7rem 0.9rem; }
.cr-admin-bar .group {
    display: inline-flex; align-items: center; gap: 0.35rem;
    margin-right: 0.8rem; padding-right: 0.8rem; margin-bottom: 0.3rem;
    border-right: 1px solid #444;
}
.cr-admin-bar .group:last-child { border-right: none; }
.cr-admin-bar .group-label {
    font-weight: bold; color: #aaa; font-size: 0.85rem; text-transform: uppercase;
    min-width: 4.5rem;
}
.cr-admin-bar form { display: inline; margin: 0; }
.cr-admin-bar .btn-mini {
    background: #2a2a2a; color: #fff; border: 1px solid #444;
    padding: 0.3rem 0.7rem; font-size: 0.9rem; cursor: pointer;
    border-radius: 4px; font-family: monospace; line-height: 1.2;
}
.cr-admin-bar .btn-mini:hover { background: #3a3a3a; border-color: #c10000; }
.cr-admin-bar .btn-mini.neg { color: #ff8080; }
.cr-admin-bar .btn-mini.pos { color: #80ff80; }
.cr-admin-bar .btn-mini.danger { background: #441a1a; }
</style>

<div id="cr-admin-bar" class="cr-admin-bar">
    <button type="button" class="cr-admin-bar-toggle" onclick="document.getElementById('cr-admin-bar').classList.toggle('collapsed'); localStorage.setItem('crAdminBarCollapsed', document.getElementById('cr-admin-bar').classList.contains('collapsed') ? '1' : '0');">
        [ ADMIN ] quick actions
    </button>
    <div class="cr-admin-bar-body d-flex flex-wrap align-items-center">

        <?php foreach ($resourceFields as [$label, $field, $deltas]): ?>
            <div class="group">
                <span class="group-label"><?= esc($label) ?></span>
                <?php foreach ($deltas as $d): ?>
                    <form hx-post="/admin/player-tools/adjust"
                          hx-headers='<?= $csrfTokenHeader ?>'
                          hx-swap="none">
                        <?= csrf_field() ?>
                        <input type="hidden" name="field"  value="<?= esc($field) ?>">
                        <input type="hidden" name="action" value="delta">
                        <input type="hidden" name="delta"  value="<?= (int) $d ?>">
                        <button type="submit" class="btn-mini <?= $d > 0 ? 'pos' : 'neg' ?>"><?= $d > 0 ? '+' . $d : $d ?></button>
                    </form>
                <?php endforeach ?>
                <form hx-post="/admin/player-tools/adjust" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field"  value="<?= esc($field) ?>">
                    <input type="hidden" name="action" value="zero">
                    <button type="submit" class="btn-mini" title="set 0">0</button>
                </form>
                <form hx-post="/admin/player-tools/adjust" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field"  value="<?= esc($field) ?>">
                    <input type="hidden" name="action" value="max">
                    <button type="submit" class="btn-mini" title="set max">max</button>
                </form>
            </div>
        <?php endforeach ?>

        <?php foreach ($progressionFields as [$label, $field, $deltas]): ?>
            <div class="group">
                <span class="group-label"><?= esc($label) ?></span>
                <?php foreach ($deltas as $d): ?>
                    <form hx-post="/admin/player-tools/adjust" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                        <?= csrf_field() ?>
                        <input type="hidden" name="field"  value="<?= esc($field) ?>">
                        <input type="hidden" name="action" value="delta">
                        <input type="hidden" name="delta"  value="<?= (int) $d ?>">
                        <button type="submit" class="btn-mini pos">+<?= number_format($d) ?></button>
                    </form>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>

        <div class="group">
            <span class="group-label">État</span>
            <form hx-post="/admin/player-tools/state" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                <?= csrf_field() ?>
                <input type="hidden" name="action"  value="force_jail">
                <input type="hidden" name="minutes" value="30">
                <button type="submit" class="btn-mini danger" title="Prison 30 min">Jail 30m</button>
            </form>
            <form hx-post="/admin/player-tools/state" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="free_jail">
                <button type="submit" class="btn-mini" title="Libere de prison">Free jail</button>
            </form>
            <form hx-post="/admin/player-tools/state" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                <?= csrf_field() ?>
                <input type="hidden" name="action"  value="force_hospital">
                <input type="hidden" name="minutes" value="30">
                <button type="submit" class="btn-mini danger" title="Cyberclinique 30 min">Hosp 30m</button>
            </form>
            <form hx-post="/admin/player-tools/state" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="free_hospital">
                <button type="submit" class="btn-mini" title="Sortie cyberclinique">Free hosp</button>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    // Restaure l'etat replie persiste.
    if (localStorage.getItem('crAdminBarCollapsed') === '1') {
        document.getElementById('cr-admin-bar').classList.add('collapsed');
    }
})();
</script>
