<?= $this->extend('layouts/main') ?>

<?php helper(['number', 'time']); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-baseline mb-3">
        <h1 class="h3 mb-0">
            <a href="/messages" class="text-decoration-none text-muted small me-2">‹ Inbox</a>
            <a href="/u/<?= esc($partner['username']) ?>" class="text-decoration-none text-dark"><?= esc($partner['username']) ?></a>
        </h1>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card mb-3">
        <ul class="list-group list-group-flush small">
            <?php if (empty($messages)): ?>
                <li class="list-group-item text-muted fst-italic text-center">
                    Aucun message. Envoie le premier ci-dessous.
                </li>
            <?php endif ?>
            <?php foreach ($messages as $m): ?>
                <?php $mine = (int) $m['sender_player_id'] === (int) $me['id']; ?>
                <li class="list-group-item d-flex <?= $mine ? 'justify-content-end' : '' ?>">
                    <div style="max-width: 38rem;" class="<?= $mine ? 'text-end' : '' ?>">
                        <div class="small text-muted mb-1">
                            <strong><?= $mine ? 'Toi' : esc($partner['username']) ?></strong>
                            · <span class="font-monospace"><?= esc(relative_short($m['created_at'])) ?></span>
                        </div>
                        <div class="p-2 border rounded <?= $mine ? 'bg-dark text-white' : 'bg-light' ?>"
                             style="white-space: pre-wrap; word-wrap: break-word;">
                            <?= esc($m['body']) ?>
                        </div>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold">Nouveau message</div>
        <div class="card-body">
            <form method="post" action="/messages/send" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="recipient_player_id" value="<?= (int) $partner['id'] ?>">
                <div class="mb-2">
                    <textarea name="body" rows="3" maxlength="<?= \App\Models\MessageModel::MAX_BODY ?>"
                              class="form-control"
                              placeholder="Écris ton message…" required></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Max <?= number_format(\App\Models\MessageModel::MAX_BODY) ?> caractères.</small>
                    <button type="submit" class="btn btn-dark btn-sm">Envoyer</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
