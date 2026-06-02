<?php
/**
 * Polling navigateur des notifications. Inclus dans le layout.
 *
 * Active uniquement si :
 *  1. L'utilisateur a clique sur 'Activer notifs nav' sur /profile (localStorage
 *     crNotifEnabled='1') ET
 *  2. La permission Notification est 'granted'.
 *
 * Poll toutes les 30s sur /notifications/poll?since=<timestamp>.
 * Pour chaque message ou attaque renvoyee : new Notification(title, body). Click =
 * navigate vers la page concernee.
 */

if (! function_exists('auth') || ! auth()->loggedIn()) return;
?>

<script>
(function () {
    // No-op si l'user n'a pas active explicitement OU permission pas granted.
    if (localStorage.getItem('crNotifEnabled') !== '1') return;
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;

    const SINCE_KEY = 'crNotifLastPoll';
    const POLL_MS   = 30000;

    function nowIso() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
            + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    async function poll() {
        const since = localStorage.getItem(SINCE_KEY) || nowIso();
        try {
            const res = await fetch('/notifications/poll?since=' + encodeURIComponent(since), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (! res.ok) return;
            const data = await res.json();
            if (! data.ok) return;

            // Message prive recu
            (data.messages || []).forEach(m => {
                const n = new Notification('Message de ' + m.sender, {
                    body: m.preview,
                    tag:  'msg-' + m.id,
                    icon: '/favicon.ico',
                });
                n.onclick = function () {
                    window.focus();
                    window.location.href = '/messages';
                };
            });

            // Combat ended ou j'etais defenseur
            (data.attacks || []).forEach(a => {
                const n = new Notification('Attaque subie : ' + a.attacker, {
                    body: 'Issue : ' + a.outcome,
                    tag:  'attack-' + a.id,
                    icon: '/favicon.ico',
                });
                n.onclick = function () {
                    window.focus();
                    window.location.href = '/profile';
                };
            });

            // Avance le curseur.
            if (data.now) localStorage.setItem(SINCE_KEY, data.now);
        } catch (e) { /* network blip, retry next tick */ }
    }

    poll();
    setInterval(poll, POLL_MS);
})();
</script>
