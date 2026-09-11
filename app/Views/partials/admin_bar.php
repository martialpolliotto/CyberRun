<?php
/**
 * Bouton admin flottant en bas a droite (a gauche du chat), uniquement pour
 * les groupes admin/superadmin.
 *
 * Pattern identique au chat_widget : icone toggle + panel expansible.
 * Etat (open/closed) persiste en localStorage. Defaut = ferme.
 *
 * Toutes les actions sont HTMX : POST sur /admin/player-tools/adjust|state,
 * reponse = partial _resources en OOB swap (rafraichit la sidebar sans reload).
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
/* Icone admin : bottom-right, juste a gauche du chat (chat occupe right:1rem, 38px de large). */
.cr-admin-toolbar {
    position: fixed; bottom: 0; right: calc(4rem + 38px + 4px); z-index: 1050;
}
.cr-admin-toolbar .cr-admin-icon {
    background: #c10000; color: #fff; border: none;
    width: 38px; height: 38px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.cr-admin-toolbar .cr-admin-icon:hover { background: #e00000; }
.cr-admin-toolbar .cr-admin-icon.open { background: #2a2a2a; }

/* Panel : ancre au-dessus de l'icone admin (alignement right edge). */
.cr-admin-panel {
    position: fixed; bottom: 38px; right: calc(4rem + 38px + 4px); z-index: 1049;
    max-width: calc(100vw - 2rem);
    background: #1a1a1a; color: #fff;
    border: 1px solid #444; border-radius: 6px 6px 0 0;
    font-size: 0.95rem;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.4);
}
.cr-admin-panel-header {
    background: #c10000; color: #fff;
    padding: 0.4rem 0.7rem;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 0.85rem; font-weight: bold; letter-spacing: 0.5px;
    text-transform: uppercase;
    border-radius: 6px 6px 0 0;
}
.cr-admin-panel-body { padding: 0.7rem 0.9rem; }

.cr-admin-panel .group {
    display: inline-flex; align-items: center; gap: 0.35rem;
    margin-right: 0.8rem; padding-right: 0.8rem; margin-bottom: 0.3rem;
    border-right: 1px solid #444;
}
.cr-admin-panel .group:last-child { border-right: none; }
.cr-admin-panel .group-label {
    font-weight: bold; color: #aaa; font-size: 0.85rem; text-transform: uppercase;
    min-width: 4.5rem;
}
.cr-admin-panel form { display: inline; margin: 0; }
.cr-admin-panel .btn-mini {
    background: #2a2a2a; color: #fff; border: 1px solid #444;
    padding: 0.3rem 0.7rem; font-size: 0.9rem; cursor: pointer;
    border-radius: 4px; font-family: monospace; line-height: 1.2;
}
.cr-admin-panel .btn-mini:hover { background: #3a3a3a; border-color: #c10000; }
.cr-admin-panel .btn-mini.neg { color: #ff8080; }
.cr-admin-panel .btn-mini.pos { color: #80ff80; }
.cr-admin-panel .btn-mini.danger { background: #441a1a; }
.cr-admin-close {
    background: none; border: none; color: #fff; padding: 0;
    cursor: pointer; font-size: 1rem; line-height: 1;
}
</style>

<div x-data="crAdminBar()">

    <!-- Toolbar icone bottom-right -->
    <div class="cr-admin-toolbar">
        <button type="button" class="cr-admin-icon" :class="{ open: open }" @click="open = !open" title="Admin quick actions">
            <i class="bi bi-tools"></i>
        </button>
    </div>

    <!-- Panel actions -->
    <div class="cr-admin-panel" x-show="open" x-cloak>
        <div class="cr-admin-panel-header">
            <span><i class="bi bi-tools me-1"></i> Admin quick actions</span>
            <button type="button" class="cr-admin-close" @click="open = false" title="Fermer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="cr-admin-panel-body d-flex flex-wrap align-items-center">

            <?php foreach ($resourceFields as [$label, $field, $deltas]): ?>
                <div class="group">
                    <span class="group-label"><?= esc($label) ?></span>
                    <?php foreach ($deltas as $d): ?>
                        <form hx-post="/admin/player-tools/adjust" hx-headers='<?= $csrfTokenHeader ?>' hx-swap="none">
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

</div>

<script>
function crAdminBar() {
    return {
        open: localStorage.getItem('crAdminBarOpen') === '1',
        init() {
            this.$watch('open', v => localStorage.setItem('crAdminBarOpen', v ? '1' : '0'));
        },
    };
}
</script>
