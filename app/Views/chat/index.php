<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Chat</h1>
            <p class="text-muted small mb-0">Polling 3s. Liens externes interdits. Mentions <code>@pseudo</code> autorisées.</p>
        </div>
    </div>

    <!-- Channels switcher -->
    <ul class="nav nav-tabs mb-2">
        <?php foreach ($channels as $c): ?>
            <li class="nav-item">
                <a class="nav-link <?= $c['key'] === $channel ? 'active' : '' ?>"
                   href="/chat/<?= esc($c['key'], 'attr') ?>"><?= esc($c['label']) ?></a>
            </li>
        <?php endforeach ?>
    </ul>

    <!-- Erreurs HTMX -->
    <div id="chat-error" class="alert alert-danger small py-2 d-none mb-2"></div>

    <!-- Liste messages + poller -->
    <div class="card mb-2">
        <div id="chat-list" class="p-1" style="height: 26rem; overflow-y: auto;">
            <?= view('chat/_messages', ['messages' => $messages, 'channel' => $channel, 'last_id' => $last_id]) ?>
        </div>
    </div>

    <!-- Form envoi -->
    <form id="chat-form"
          hx-post="/chat/send"
          hx-swap="none"
          hx-on::after-request="if (event.detail.successful) this.reset()"
          class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="channel" value="<?= esc($channel, 'attr') ?>">
        <input type="text" name="body" maxlength="500" required autocomplete="off"
               class="form-control" placeholder="Tape ton message… (@pseudo pour mentionner)">
        <button type="submit" class="btn btn-dark">Envoyer</button>
    </form>

</div>

<script>
(function () {
    const list = document.getElementById('chat-list');
    const err  = document.getElementById('chat-error');

    // Auto-scroll en bas si on est deja proche du bas (sinon laisse l'utilisateur lire).
    let stickToBottom = true;
    list.addEventListener('scroll', () => {
        stickToBottom = (list.scrollHeight - list.scrollTop - list.clientHeight) < 50;
    });

    // Apres chaque settle HTMX dans la liste, scroll si on etait colle.
    document.body.addEventListener('htmx:afterSettle', (e) => {
        if (list.contains(e.target) || e.target === list) {
            if (stickToBottom) list.scrollTop = list.scrollHeight;
        }
    });
    // Initial scroll au chargement.
    list.scrollTop = list.scrollHeight;

    // Affichage erreur cote serveur (HX-Trigger chatError -> JSON).
    document.body.addEventListener('chatError', (e) => {
        err.textContent = e.detail.value || 'Erreur.';
        err.classList.remove('d-none');
        setTimeout(() => err.classList.add('d-none'), 4000);
    });
})();
</script>

<?= $this->endSection() ?>
