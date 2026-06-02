<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $myId       = (int) $me['id'];
    $isAttacker = (bool) $is_attacker;
    $myHp       = $isAttacker ? (int) $combat['attacker_hp_remaining'] : (int) $combat['defender_hp_remaining'];
    $oppHp      = $isAttacker ? (int) $combat['defender_hp_remaining'] : (int) $combat['attacker_hp_remaining'];
    $myMax      = $isAttacker ? (int) $combat['attacker_hp_initial']   : (int) $combat['defender_hp_initial'];
    $oppMax     = $isAttacker ? (int) $combat['defender_hp_initial']   : (int) $combat['attacker_hp_initial'];

    $myPct  = (int) round(($myHp  / max(1, $myMax))  * 100);
    $oppPct = (int) round(($oppHp / max(1, $oppMax)) * 100);

    $isMyTurn = $combat['status'] === 'ongoing' && (int) $combat['current_turn_player_id'] === $myId;
    $iWon     = $combat['status'] === 'ended_attacker_won' && $isAttacker
             || $combat['status'] === 'ended_defender_won' && ! $isAttacker;
    $needsPost = $iWon && empty($combat['post_action']);
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <h1 class="h3 mb-3">Combat</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Life bars 2 cotés -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between small text-uppercase fw-semibold mb-1">
                        <span>Toi (<?= esc($me_username) ?>)</span>
                        <span class="font-monospace text-muted"><?= $myHp ?> / <?= $myMax ?></span>
                    </div>
                    <div class="progress cr-bar-notched" style="height: 8px;">
                        <div class="progress-bar cr-bar-life" style="width: <?= $myPct ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between small text-uppercase fw-semibold mb-1">
                        <span><a href="/u/<?= esc($opponent_username) ?>" class="text-dark text-decoration-none"><?= esc($opponent_username) ?></a></span>
                        <span class="font-monospace text-muted"><?= $oppHp ?> / <?= $oppMax ?></span>
                    </div>
                    <div class="progress cr-bar-notched" style="height: 8px;">
                        <div class="progress-bar cr-bar-life" style="width: <?= $oppPct ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card mb-3">
        <div class="card-body">
            <?php if ($combat['status'] === 'ongoing'): ?>
                <?php if ($isMyTurn): ?>
                    <div class="small text-muted mb-2">À toi de jouer :</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="post" action="/combat/<?= (int) $combat['id'] ?>/turn" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="attack">
                            <button type="submit" class="btn btn-dark">⚔ Attaquer</button>
                        </form>
                        <?php if (! $isAttacker): ?>
                            <form method="post" action="/combat/<?= (int) $combat['id'] ?>/turn" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="guard">
                                <button type="submit" class="btn btn-outline-dark">🛡 Garder</button>
                            </form>
                        <?php endif ?>
                        <form method="post" action="/combat/<?= (int) $combat['id'] ?>/turn" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="flee">
                            <button type="submit" class="btn btn-outline-dark">↻ Fuir</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="text-muted fst-italic mb-0">En attente du tour adverse… (rafraîchis dans quelques secondes)</p>
                <?php endif ?>
            <?php elseif ($needsPost): ?>
                <div class="small text-uppercase fw-semibold text-muted mb-2">Tu as gagné — que fais-tu ?</div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="/combat/<?= (int) $combat['id'] ?>/post/mug" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">💰 Mug (voler crédits)</button>
                    </form>
                    <form method="post" action="/combat/<?= (int) $combat['id'] ?>/post/hospitalize" class="m-0" onsubmit="return confirm('Hospitalize ? Réclame aussi toutes les bounties actives sur la cible.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">✚ Hospitalize</button>
                    </form>
                    <form method="post" action="/combat/<?= (int) $combat['id'] ?>/post/leave" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-dark">↩ Partir</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="text-center small">
                    <?php
                        $status = $combat['status'];
                        $msg = match ($status) {
                            'ended_attacker_won'  => $isAttacker ? 'Victoire.' : 'Tu es K.O.',
                            'ended_defender_won'  => $isAttacker ? 'Tu es K.O.' : 'Victoire.',
                            'ended_attacker_fled' => $isAttacker ? 'Tu as fui.' : 'Ta cible a fui.',
                            'ended_defender_fled' => $isAttacker ? 'Ta cible a fui.' : 'Tu as fui.',
                            'resolved'            => 'Combat terminé et résolu.',
                            default               => 'Combat terminé.',
                        };
                    ?>
                    <p class="mb-2"><?= esc($msg) ?></p>
                    <?php if (! empty($combat['mug_amount']) && (int) $combat['mug_amount'] > 0): ?>
                        <p class="small text-muted">Crédits dérobés : <?= number_format((int) $combat['mug_amount']) ?> ¢</p>
                    <?php endif ?>
                    <a href="/u/<?= esc($opponent_username) ?>" class="btn btn-outline-dark btn-sm">Retour au profil</a>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Historique des tours -->
    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold">Historique des tours (<?= count($turns) ?>)</div>
        <ul class="list-group list-group-flush small">
            <?php if (empty($turns)): ?>
                <li class="list-group-item text-muted fst-italic">Pas encore d'action.</li>
            <?php endif ?>
            <?php foreach ($turns as $t): ?>
                <?php
                    $authorIsMe = (int) $t['turn_player_id'] === $myId;
                    $authorName = $authorIsMe ? 'Toi' : $opponent_username;
                ?>
                <li class="list-group-item">
                    <span class="font-monospace text-muted small">T<?= (int) $t['id'] ?></span> ·
                    <strong><?= esc($authorName) ?></strong>
                    <?= esc($t['narrative']) ?>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

</div>

<?= $this->endSection() ?>
