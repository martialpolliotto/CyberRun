<?php
/**
 * Widget chat flottant style Torn : toolbar bottom-right + panel expansible.
 *
 * Etat persiste en localStorage (open + channel selectionne).
 * Polling HTMX 3s actif uniquement quand le panel est ouvert.
 *
 * Pre-requis : sidebar.php a deja resolu $player, $unreadMessages, etc. — ici on
 * recalcule a partir de auth() pour rester autonome.
 */

if (! function_exists('auth') || ! auth()->loggedIn()) return;

$user   = auth()->user();
$player = model(\App\Models\PlayerModel::class)->findByUserId((int) $user->id);
if ($player === null) return;

$channels = (new \App\Services\ChatService())->visibleChannels($player);
$defaultChannel = $channels[0]['key'] ?? 'global';
?>

<style>
[x-cloak] { display: none !important; }
.cr-chat-toolbar {
    position: fixed; bottom: 0; right: 1rem; z-index: 1050;
    display: flex; gap: 2px;
}
.cr-chat-toolbar .cr-chat-icon {
    background: #212529; color: #fff; border: none;
    width: 38px; height: 38px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    position: relative;
}
.cr-chat-toolbar .cr-chat-icon:hover { background: #343a40; }
.cr-chat-toolbar .cr-chat-icon.muted { background: #495057; color: #adb5bd; }
.cr-chat-toolbar .cr-chat-badge {
    position: absolute; top: 4px; right: 4px;
    background: #dc3545; color: #fff;
    width: 8px; height: 8px; border-radius: 50%;
}

.cr-chat-panel {
    position: fixed; bottom: 38px; right: 1rem; z-index: 1049;
    width: 24rem; max-width: calc(100vw - 2rem); height: 28rem;
    background: #fff; border: 1px solid #212529;
    display: flex; flex-direction: column;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.15);
}
.cr-chat-header {
    background: #212529; color: #fff;
    padding: 0.4rem 0.6rem;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 0.85rem; flex-shrink: 0;
}
.cr-chat-tabs {
    display: flex; border-bottom: 1px solid #dee2e6; flex-shrink: 0;
}
.cr-chat-tabs a {
    padding: 0.3rem 0.6rem; font-size: 0.8rem; text-decoration: none;
    color: #6c757d; border-right: 1px solid #dee2e6; cursor: pointer;
}
.cr-chat-tabs a.active { color: #212529; font-weight: 600; background: #f8f9fa; }
.cr-chat-tabs a:hover { background: #f1f3f5; color: #212529; }

.cr-chat-list {
    flex-grow: 1; overflow-y: auto; padding: 0.25rem;
    background: #fff;
}
.cr-chat-form {
    border-top: 1px solid #dee2e6;
    padding: 0.4rem; display: flex; gap: 0.4rem; flex-shrink: 0;
}
.cr-chat-form input { font-size: 0.85rem; }
.cr-chat-error {
    background: #f8d7da; color: #842029;
    padding: 0.3rem 0.6rem; font-size: 0.75rem; flex-shrink: 0;
}
</style>

<div x-data="crChatWidget()">

    <!-- Toolbar bottom-right -->
    <div class="cr-chat-toolbar">
        <button type="button" class="cr-chat-icon" @click="toggle()" title="Chat">
            <i class="bi bi-chat-dots-fill"></i>
            <span class="cr-chat-badge" x-show="unread && !open" x-cloak></span>
        </button>
    </div>

    <!-- Panel chat -->
    <div class="cr-chat-panel" x-show="open" x-cloak>

        <!-- Header -->
        <div class="cr-chat-header">
            <strong x-text="channelLabel()">Global</strong>
            <span class="d-flex gap-2">
                <a href="/chat" class="text-white-50 text-decoration-none" title="Vue pleine page">
                    <i class="bi bi-arrows-fullscreen"></i>
                </a>
                <button type="button" class="btn btn-sm btn-link text-white p-0" @click="open = false" title="Fermer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </span>
        </div>

        <!-- Tabs channels -->
        <div class="cr-chat-tabs">
            <?php foreach ($channels as $c): ?>
                <a href="#" @click.prevent="switchChannel('<?= esc($c['key'], 'attr') ?>')"
                   :class="{ 'active': channel === '<?= esc($c['key'], 'attr') ?>' }">
                    <?= esc($c['label']) ?>
                </a>
            <?php endforeach ?>
        </div>

        <!-- Erreurs -->
        <div class="cr-chat-error" x-show="error" x-cloak x-text="error"></div>

        <!-- Liste messages (HTMX swap quand channel change) -->
        <div id="cr-chat-list" class="cr-chat-list"
             :hx-get="'/chat/init/' + channel"
             hx-trigger="load delay:50ms"
             hx-swap="innerHTML">
            <span class="text-muted small fst-italic">Chargement…</span>
        </div>

        <!-- Form envoi -->
        <form class="cr-chat-form m-0"
              hx-post="/chat/send"
              hx-swap="none"
              hx-on::after-request="if (event.detail.successful) this.querySelector('input[name=body]').value=''">
            <?= csrf_field() ?>
            <input type="hidden" name="channel" :value="channel">
            <input type="text" name="body" maxlength="500" required autocomplete="off"
                   class="form-control form-control-sm" placeholder="Message… (@pseudo)">
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-send"></i>
            </button>
        </form>

    </div>

</div>

<script>
function crChatWidget() {
    return {
        open: localStorage.getItem('crChatOpen') === '1',
        channel: localStorage.getItem('crChatChannel') || '<?= esc($defaultChannel, 'attr') ?>',
        unread: false,
        error: '',
        stickToBottom: true,

        init() {
            this.$watch('open', v => {
                localStorage.setItem('crChatOpen', v ? '1' : '0');
                if (v) {
                    this.unread = false;
                    this.$nextTick(() => this.scrollBottom());
                }
            });
            this.$watch('channel', v => {
                localStorage.setItem('crChatChannel', v);
            });

            // Quand de nouveaux messages arrivent (HTMX afterSettle sur cr-chat-list), auto-scroll.
            document.body.addEventListener('htmx:afterSettle', (e) => {
                const list = document.getElementById('cr-chat-list');
                if (! list) return;
                if (list.contains(e.target) || e.target === list) {
                    if (this.stickToBottom) this.scrollBottom();
                    if (! this.open) this.unread = true;
                }
            });

            // Detecte scroll utilisateur pour gerer stickToBottom.
            this.$nextTick(() => {
                const list = document.getElementById('cr-chat-list');
                if (list) {
                    list.addEventListener('scroll', () => {
                        this.stickToBottom = (list.scrollHeight - list.scrollTop - list.clientHeight) < 50;
                    });
                }
            });

            // Erreurs serveur via HX-Trigger.
            document.body.addEventListener('chatError', (e) => {
                this.error = e.detail.value || 'Erreur.';
                setTimeout(() => this.error = '', 4000);
            });
        },

        toggle() { this.open = ! this.open; },

        switchChannel(ch) {
            if (this.channel === ch) return;
            this.channel = ch;
            const list = document.getElementById('cr-chat-list');
            if (list) {
                list.setAttribute('hx-get', '/chat/init/' + ch);
                htmx.process(list);
                htmx.ajax('GET', '/chat/init/' + ch, { target: '#cr-chat-list', swap: 'innerHTML' });
            }
        },

        channelLabel() {
            const labels = <?= json_encode(array_column($channels, 'label', 'key'), JSON_UNESCAPED_UNICODE) ?>;
            return labels[this.channel] || this.channel;
        },

        scrollBottom() {
            const list = document.getElementById('cr-chat-list');
            if (list) list.scrollTop = list.scrollHeight;
        },
    };
}
</script>
