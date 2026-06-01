<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $statusBadge = static function (string $status): string {
        return match ($status) {
            'jail'     => '<span class="badge bg-dark">En prison</span>',
            'hospital' => '<span class="badge bg-secondary">Cyberclinique</span>',
            default    => '<span class="badge bg-light text-muted">Libre</span>',
        };
    };

    $isSelf = $me !== null && (int) $me['id'] === (int) $profile['id'];
    $myId   = $me !== null ? (int) $me['id'] : 0;
    $targetId = (int) $profile['id'];
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="small mb-3">
        <a href="/players" class="text-muted text-decoration-none">← Tous les joueurs</a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Carte identité -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="h3 mb-1"><?= esc($profile['username']) ?></h1>
                    <p class="text-muted small mb-0">
                        Niveau <strong><?= (int) $profile['level'] ?></strong> ·
                        inscrit le <?= esc(\CodeIgniter\I18n\Time::parse($profile['joined_at'])->toLocalizedString('d MMMM yyyy')) ?>
                    </p>
                </div>
                <div class="text-end"><?= $statusBadge((string) $profile['_status']) ?></div>
            </div>

            <?php if (! $isSelf && $me !== null): ?>
                <!-- Barre d'actions PvP / sociales -->
                <hr>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <form method="post" action="/attack/<?= $targetId ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark btn-sm" title="Attaquer">⚔ Attaquer</button>
                    </form>
                    <a href="/messages/thread/<?= $targetId ?>" class="btn btn-outline-dark btn-sm" title="Envoyer un message">✉ Msg</a>
                    <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modal-transfer" title="Envoyer des crédits">¢ Argent</button>
                    <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modal-bounty" title="Placer une prime">☠ Prime</button>
                    <form method="post" action="/spy/<?= $targetId ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark btn-sm" title="Espionner ses stats">◉ Espion</button>
                    </form>

                    <span class="vr"></span>

                    <?php
                        $rm = model(\App\Models\PlayerRelationModel::class);
                        $isFriend = $rm->has($myId, $targetId, 'friend');
                        $isEnemy  = $rm->has($myId, $targetId, 'enemy');
                        $isTarget = $rm->has($myId, $targetId, 'target');
                    ?>
                    <form method="post" action="/relations/friend/<?= $targetId ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $isFriend ? 'btn-dark' : 'btn-outline-dark' ?>" title="Ami">★ Ami</button>
                    </form>
                    <form method="post" action="/relations/enemy/<?= $targetId ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $isEnemy ? 'btn-dark' : 'btn-outline-dark' ?>" title="Ennemi">✕ Ennemi</button>
                    </form>
                    <form method="post" action="/relations/target/<?= $targetId ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= $isTarget ? 'btn-dark' : 'btn-outline-dark' ?>" title="Cible">⊙ Cible</button>
                    </form>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Stats combat publiques (pas de stats brutes, juste des compteurs) -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold">Statistiques de combat</div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <?php
                    $cs = $combat_stats;
                    $cells = [
                        'Attaques réussies' => (int) $cs['attacks_won'],
                        'Attaques ratées'   => (int) $cs['attacks_lost'],
                        'Défenses tenues'   => (int) $cs['defenses_won'],
                        'Défenses perdues'  => (int) $cs['defenses_lost'],
                        'Kills'             => (int) $cs['kills'],
                        'Morts'             => (int) $cs['deaths'],
                        'Kill streak'       => (int) $cs['kill_streak'],
                        'Meilleur streak'   => (int) $cs['best_kill_streak'],
                    ];
                ?>
                <?php foreach ($cells as $label => $value): ?>
                    <div class="col-6 col-md-3">
                        <div class="small text-muted text-uppercase"><?= esc($label) ?></div>
                        <div class="fs-4 fw-bold font-monospace"><?= number_format($value) ?></div>
                    </div>
                <?php endforeach ?>
            </div>
            <p class="form-text text-center mt-3 mb-0">Les stats brutes (Force / Blindage / Réflexes / Hack) sont privées. Utilise <strong>Espion</strong> pour les obtenir (à venir).</p>
        </div>
    </div>

    <!-- Bounty actives sur la cible -->
    <?php if (! empty($active_bounties)): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                <span>Primes actives sur sa tête</span>
                <span class="badge bg-dark"><?= count($active_bounties) ?></span>
            </div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($active_bounties as $b): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <strong>¢<?= number_format((int) $b['amount']) ?></strong>
                            par <a href="/u/<?= esc($b['placer_username']) ?>" class="text-dark text-decoration-none fw-semibold"><?= esc($b['placer_username']) ?></a>
                            <?php if (! empty($b['message'])): ?>
                                <div class="text-muted fst-italic mt-1">« <?= esc($b['message']) ?> »</div>
                            <?php endif ?>
                        </div>
                        <?php if ($me !== null && (int) $b['placer_player_id'] === (int) $me['id']): ?>
                            <form method="post" action="/bounties/<?= (int) $b['id'] ?>/cancel" class="m-0"
                                  onsubmit="return confirm('Annuler ta prime et récupérer les <?= number_format((int) $b['amount']) ?> credits ?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-dark" title="Annuler ta prime + refund">×</button>
                            </form>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <!-- Bazaar du joueur (visible publiquement) -->
    <?php if (! empty($bazaar_listings)): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                <span>Bazaar de <?= esc($profile['username']) ?></span>
                <span class="text-muted"><?= count($bazaar_listings) ?> listing<?= count($bazaar_listings) > 1 ? 's' : '' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Prix unitaire</th>
                            <?php if (! $isSelf && $me !== null): ?>
                                <th></th>
                            <?php endif ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bazaar_listings as $l): ?>
                            <tr>
                                <td>
                                    <strong><?= esc($l['item_name']) ?></strong>
                                    <?php if (! empty($l['item_consumable_type'])): ?>
                                        <span class="badge bg-light text-dark border ms-1"><?= esc($l['item_consumable_type']) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end font-monospace"><?= (int) $l['quantity'] ?></td>
                                <td class="text-end font-monospace"><?= number_format((int) $l['unit_price']) ?>¢</td>
                                <?php if (! $isSelf && $me !== null): ?>
                                    <td class="text-end">
                                        <form method="post" action="/bazaar/listings/<?= (int) $l['id'] ?>/buy" class="d-flex gap-1 justify-content-end m-0"
                                              onsubmit="return confirm('Confirmer l\'achat ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return_to" value="/u/<?= esc($profile['username']) ?>">
                                            <input type="number" name="quantity" min="1" max="<?= (int) $l['quantity'] ?>" value="1"
                                                   class="form-control form-control-sm font-monospace" style="width: 4.5rem;">
                                            <button type="submit" class="btn btn-dark btn-sm">Acheter</button>
                                        </form>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>

    <!-- Section bust/bail si en prison (déjà là avant la refonte) -->
    <?php if ($profile['_status'] === 'jail' && ! $isSelf && $me !== null): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Le faire sortir</div>
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="/bust/<?= $targetId ?>" class="m-0" onsubmit="return confirm('Tenter un bust ? Échec = toi en prison.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark">Bust (chance <?= (int) ($profile['_bust_pct'] ?? 0) ?>%)</button>
                    </form>
                    <form method="post" action="/bail/<?= $targetId ?>" class="m-0" onsubmit="return confirm('Payer la caution (<?= (int) ($profile['_bail_cost'] ?? 0) ?> credits) ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">Payer caution (¢<?= number_format((int) ($profile['_bail_cost'] ?? 0)) ?>)</button>
                    </form>
                </div>
                <p class="form-text mt-2 mb-0">Bust : risqué, consomme de la nerve. Bail : garanti, coûte des crédits.</p>
            </div>
        </div>
    <?php endif ?>

    <!-- Modal envoi crédits -->
    <?php if (! $isSelf && $me !== null): ?>
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="/transfer">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_player_id" value="<?= $targetId ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Envoyer des crédits à <?= esc($profile['username']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small">Montant (¢)</label>
                            <input type="number" name="amount" min="1" max="<?= (int) $me['credits'] ?>" required class="form-control font-monospace">
                            <div class="form-text">Ton solde : <strong>¢<?= number_format((int) $me['credits']) ?></strong></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-dark">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal placer prime -->
    <div class="modal fade" id="modal-bounty" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="/bounties/place" onsubmit="return confirm('Confirmer la prime ? Une fois placée, elle ne peut PAS être annulée ni remboursée.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_player_id" value="<?= $targetId ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Placer une prime sur <?= esc($profile['username']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <?= view('partials/alert', ['variant' => 'warning', 'message' => 'Une fois placée, la prime est définitive. Aucun remboursement possible.']) ?>
                        <div class="mb-3">
                            <label class="form-label small">Montant de la prime (¢)</label>
                            <input type="number" name="amount" min="1" max="<?= (int) $me['credits'] ?>" required class="form-control font-monospace">
                            <div class="form-text">Le tueur qui finalisera la cible empochera le pot. Ton solde : <strong>¢<?= number_format((int) $me['credits']) ?></strong>.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Message (optionnel)</label>
                            <textarea name="message" rows="2" maxlength="255" class="form-control" placeholder="« Cette ordure a vendu ma soeur aux corpos. »"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-dark">Placer la prime</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
