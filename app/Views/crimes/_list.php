<?php
/**
 * Partial : liste des crimes. Si une tentative vient d'avoir lieu, son resultat
 * apparait directement dans la box du crime concerne (pas au-dessus de la liste).
 *
 * @var array<string,mixed> $player
 * @var array<int, array<string,mixed>> $crimes
 * @var string|null $flash_variant     'success' | 'danger' | null
 * @var string|null $flash_message
 * @var int|null    $last_attempted_id Si fourni, le flash est rendu dans la card
 *                                     correspondante (sinon en tete de liste, cas du
 *                                     1er render avec flash de session).
 */
helper('number');
$flash_variant          = $flash_variant ?? null;
$flash_message          = $flash_message ?? null;
$last_attempted_id      = $last_attempted_id ?? null;
$last_attempted_outcome = $last_attempted_outcome ?? null;
?>
<div id="crime-list">
    <?php // Si on n'a pas de cible (1er render avec flash session), on l'affiche au-dessus.
        if ($flash_message !== null && $last_attempted_id === null): ?>
        <?= view('partials/alert', ['variant' => $flash_variant ?? 'info', 'message' => $flash_message]) ?>
    <?php endif ?>

    <?php if (empty($crimes)): ?>
        <p class="text-muted fst-italic small">Aucun crime configuré dans cette catégorie.</p>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($crimes as $c): ?>
                <?php $isLastAttempted = $last_attempted_id !== null && (int) $last_attempted_id === (int) $c['id']; ?>
                <div class="card <?= $c['_unlocked'] ? '' : 'text-muted' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="flex-grow-1" style="min-width: 16rem;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h2 class="h6 mb-0"><?= esc($c['name']) ?></h2>
                                    <?php if (! $c['_unlocked']): ?>
                                        <span class="badge bg-secondary">verrouillé · <?= (int) $c['min_category_xp'] ?> XP</span>
                                    <?php endif ?>
                                    <?php if ($c['_time_bonus_on']): ?>
                                        <span class="badge bg-dark">+<?= (int) $c['time_bonus_pct'] ?>% bonus horaire</span>
                                    <?php endif ?>
                                </div>
                                <?php if (! empty($c['description'])): ?>
                                    <p class="small mb-2"><?= esc($c['description']) ?></p>
                                <?php endif ?>
                                <div class="d-flex flex-wrap gap-3 small">
                                    <span><span class="text-muted text-uppercase">Nerve</span> <strong><?= (int) $c['nerve_cost'] ?></strong></span>
                                    <span><span class="text-muted text-uppercase">Réussite</span> <strong><?= (int) $c['_success_pct'] ?>%</strong></span>
                                    <span><span class="text-muted text-uppercase">Échec critique</span> <strong><?= (int) $c['critical_fail_pct'] ?>%</strong></span>
                                    <span><span class="text-muted text-uppercase">Gain</span> <strong>¢<?= number_format((int) $c['reward_credits_min']) ?>–<?= number_format((int) $c['reward_credits_max']) ?></strong></span>
                                    <span><span class="text-muted text-uppercase">XP</span> <strong>+<?= (int) $c['reward_xp'] ?> joueur / +<?= (int) $c['reward_category_xp'] ?> cat.</strong></span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Échec critique → <?= $c['critical_destination'] === 'hospital' ? 'cyberclinique' : 'prison' ?>
                                    (<?= (int) $c['critical_minutes_min'] ?>–<?= (int) $c['critical_minutes_max'] ?> min).
                                </div>
                            </div>
                            <div>
                                <?php if ($c['_unlocked']): ?>
                                    <?php
                                        $disabled  = (int) $player['nerve_current'] < (int) $c['nerve_cost'];
                                        $outcome   = $isLastAttempted ? ($last_attempted_outcome ?? '') : '';
                                        $btnLabel  = $disabled ? 'Nerve insuffisante' : 'Tenter (−' . (int) $c['nerve_cost'] . ' NRV)';
                                    ?>
                                    <button type="button"
                                            <?= $disabled ? 'disabled' : '' ?>
                                            class="btn btn-dark cr-htmx-btn"
                                            x-data="{ outcome: <?= esc(json_encode($outcome), 'attr') ?>, init() { if (this.outcome) setTimeout(() => this.outcome = '', 1800); } }"
                                            hx-post="/crimes/attempt/<?= (int) $c['id'] ?>"
                                            hx-headers='{"X-CSRF-TOKEN":"<?= csrf_hash() ?>"}'
                                            hx-target="#crime-list"
                                            hx-swap="outerHTML"
                                            hx-disabled-elt="this"
                                            x-bind:disabled="outcome !== ''">
                                        <span x-show="outcome === ''" class="cr-btn-text"><?= esc($btnLabel) ?></span>
                                        <span class="cr-btn-spinner spinner-border spinner-border-sm" role="status"></span>
                                        <i x-show="outcome === 'success'" x-cloak class="bi bi-check-circle-fill text-success"></i>
                                        <i x-show="outcome === 'fail'"    x-cloak class="bi bi-x-circle-fill text-danger"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" disabled>Verrouillé</button>
                                <?php endif ?>
                            </div>
                        </div>
                        <?php if ($isLastAttempted && $flash_message !== null): ?>
                            <?php $flashClass = $flash_variant === 'success' ? 'cr-flash-success' : 'cr-flash-danger'; ?>
                            <div class="mt-3 <?= $flashClass ?>">
                                <?= view('partials/alert', ['variant' => $flash_variant ?? 'info', 'message' => $flash_message]) ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
