<?= $this->extend('layouts/main') ?>

<?php helper('time'); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <h1 class="h3 mb-3">Messages</h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Conversations</span>
            <span class="text-muted"><?= count($threads) ?> thread<?= count($threads) > 1 ? 's' : '' ?></span>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (empty($threads)): ?>
                <li class="list-group-item text-muted fst-italic text-center small">
                    Pas encore de messages. Va sur la fiche d'un joueur et clique sur l'icône Msg pour démarrer une conversation.
                </li>
            <?php endif ?>
            <?php foreach ($threads as $t): ?>
                <?php $isUnread = (int) $t['unread'] > 0; ?>
                <li class="list-group-item">
                    <a href="/messages/thread/<?= (int) $t['partner_player_id'] ?>"
                       class="d-flex gap-3 align-items-center text-decoration-none text-dark">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <strong class="<?= $isUnread ? 'text-dark' : 'text-muted' ?>"><?= esc($t['partner_username']) ?></strong>
                                <span class="text-muted small font-monospace"><?= esc(relative_short($t['last_at'])) ?></span>
                            </div>
                            <div class="small <?= $isUnread ? 'fw-semibold' : 'text-muted' ?> text-truncate" style="max-width: 38rem;">
                                <?= esc(mb_substr((string) $t['last_body'], 0, 160)) ?>
                            </div>
                        </div>
                        <?php if ($isUnread): ?>
                            <span class="badge bg-dark"><?= (int) $t['unread'] ?></span>
                        <?php endif ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

</div>

<?= $this->endSection() ?>
