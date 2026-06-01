<?php
/**
 * Partial : nouveaux messages a injecter dans la liste + element poller mis a jour.
 * Renvoye par /chat/poll/{channel}/{lastId}.
 *
 * Le DOM resultant : N <div class="chat-msg"> suivis d'un nouveau #chat-poller qui
 * remplacera l'ancien (hx-swap=outerHTML).
 */

$renderBody = static function (string $raw): string {
    $body = esc($raw);
    // @username -> lien vers /u/X
    $body = preg_replace_callback('/@(\w{2,32})/u', static function ($m) {
        return '<a href="/u/' . esc($m[1], 'attr') . '" class="text-decoration-none fw-semibold">@' . esc($m[1]) . '</a>';
    }, $body) ?? $body;
    return $body;
};

$renderTime = static function (string $datetime): string {
    return esc(substr($datetime, 11, 5)); // HH:MM
};
?>

<?php foreach ($messages as $m): ?>
    <div class="chat-msg small d-flex gap-2 px-2 py-1">
        <span class="text-muted font-monospace" style="width: 3rem; flex-shrink: 0;"><?= $renderTime((string) $m['created_at']) ?></span>
        <span class="fw-semibold" style="flex-shrink: 0;">
            <?php if (! empty($m['faction_tag'])): ?>
                <span class="text-muted">[<?= esc($m['faction_tag']) ?>]</span>
            <?php endif ?>
            <a href="/u/<?= esc($m['username']) ?>" class="text-dark text-decoration-none"><?= esc($m['username']) ?></a>
            <span class="text-muted">:</span>
        </span>
        <span class="flex-grow-1" style="white-space: pre-wrap; word-wrap: break-word;"><?= $renderBody((string) $m['body']) ?></span>
    </div>
<?php endforeach ?>

<div id="chat-poller"
     hx-get="/chat/poll/<?= esc($channel, 'attr') ?>/<?= (int) $last_id ?>"
     hx-trigger="every 3s, chatSent from:body"
     hx-swap="outerHTML"></div>
