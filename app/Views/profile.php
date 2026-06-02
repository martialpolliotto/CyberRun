<?= $this->extend('layouts/main') ?>

<?php
    helper('number');
    $xpPct = (int) round(($player['xp'] / max(1, $xpToNext)) * 100);

    $statLabels = [
        'force'    => 'Force',
        'blindage' => 'Blindage',
        'reflexes' => 'Réflexes',
        'hack'     => 'Hack',
    ];
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <!-- Identité -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Profil</span>
            <span class="d-flex gap-3">
                <button type="button" id="cr-notif-toggle" class="btn btn-link p-0 text-decoration-none text-muted small">
                    <i class="bi bi-bell"></i> <span id="cr-notif-toggle-label">Activer notifs</span>
                </button>
                <button type="button" class="btn btn-link p-0 text-decoration-none text-muted small"
                        onclick="localStorage.removeItem('crTutorialDone'); window.location.reload();">
                    Refaire le tutoriel
                </button>
                <a href="/profile/edit" class="text-decoration-none text-muted small">Personnaliser</a>
                <a href="/profile/data" class="text-decoration-none text-muted small">Mes données</a>
            </span>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-2">
                <?php if (! empty($player['avatar_path'])): ?>
                    <img src="<?= esc($player['avatar_path']) ?>" alt="avatar"
                         class="rounded border" style="width: 72px; height: 72px; object-fit: cover; flex-shrink: 0;">
                <?php endif ?>
                <div class="flex-grow-1">
                    <h1 class="h3 mb-1"><?= esc($user->username) ?></h1>
                    <?php if (! empty($player['signature'])): ?>
                        <p class="text-muted fst-italic small mb-1"><?= esc($player['signature']) ?></p>
                    <?php endif ?>
                    <div class="d-flex flex-wrap gap-3 small mb-2">
                        <span>Niveau <strong><?= (int) $player['level'] ?></strong></span>
                        <span class="text-muted">XP <?= number_format($player['xp']) ?> / <?= number_format($xpToNext) ?></span>
                        <?php if (! empty($player['in_hospital_until'])): ?>
                            <span class="fw-bold">[ EN CYBERCLINIQUE ]</span>
                        <?php endif ?>
                    </div>
                    <div class="progress cr-bar-notched" style="height: 6px;">
                        <div class="progress-bar cr-bar-xp" style="width: <?= $xpPct ?>%"></div>
                    </div>
                </div>
            </div>
            <?php if (! empty($player['bio'])): ?>
                <hr class="my-3">
                <div class="small" style="white-space: pre-wrap; word-wrap: break-word;"><?= esc($player['bio']) ?></div>
            <?php endif ?>
        </div>
    </div>

    <!-- Ressources -->
    <div class="row g-3 mb-3">
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Life',    'current' => $player['hp_current'],     'max' => $player['hp_max'],     'color' => 'hp']) ?></div>
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Énergie', 'current' => $player['energy_current'], 'max' => $player['energy_max'], 'color' => 'energy']) ?></div>
        <div class="col-md-4"><?= view('partials/resource_bar', ['label' => 'Nerve',   'current' => $player['nerve_current'],  'max' => $player['nerve_max'],  'color' => 'nerve']) ?></div>
    </div>

    <!-- Stats (base + bonus = total) -->
    <h2 class="small text-uppercase text-muted mb-2">Stats de combat</h2>
    <div class="row g-3 mb-3">
        <?php foreach ($statLabels as $key => $label): ?>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body p-3">
                        <div class="small text-muted text-uppercase"><?= esc($label) ?></div>
                        <div class="fs-3 fw-bold mt-1"><?= number_format($stats['total'][$key]) ?></div>
                        <?php if ($stats['bonus'][$key] > 0): ?>
                            <div class="small text-muted mt-1">
                                <?= $stats['base'][$key] ?> + <?= $stats['bonus'][$key] ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Crédits -->
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">Solde</span>
            <span class="fs-4 fw-bold">¢<?= number_format($player['credits']) ?></span>
        </div>
    </div>

    <!-- Derniers attaquants : revenge list -->
    <?php if (! empty($recent_attacks)): ?>
        <div class="card">
            <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                <span>Tes derniers attaquants</span>
                <span class="text-muted"><?= count($recent_attacks) ?></span>
            </div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($recent_attacks as $a):
                    // L'attaquant a "won" si le combat ended_attacker_won OR (status='resolved' AND winner=attacker).
                    $attackerWon = in_array($a['status'], ['ended_attacker_won', 'resolved'], true);
                    // post_action determine ce qu'il a fait apres avoir gagne.
                    $outcomeLabel = 'duel';
                    $outcomeClass = 'text-muted';
                    if ($a['post_action'] === 'hospitalize') {
                        $outcomeLabel = 'hospitalisé';
                        $outcomeClass = 'text-danger';
                    } elseif ($a['post_action'] === 'mug') {
                        $outcomeLabel = 'volé ' . number_format((int) $a['mug_amount']) . '¢';
                        $outcomeClass = 'text-danger';
                    } elseif ($a['post_action'] === 'leave') {
                        $outcomeLabel = 'parti';
                    } elseif (in_array($a['status'], ['ended_attacker_fled', 'ended_defender_fled'], true)) {
                        $outcomeLabel = 'fuite';
                    } elseif ($a['status'] === 'ended_defender_won') {
                        $outcomeLabel = 'tu l\'as battu';
                        $outcomeClass = 'text-success';
                    }
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div class="flex-grow-1">
                            <a href="/u/<?= esc($a['attacker_username']) ?>" class="text-dark fw-semibold text-decoration-none"><?= esc($a['attacker_username']) ?></a>
                            <span class="<?= $outcomeClass ?>">— <?= esc($outcomeLabel) ?></span>
                            <div class="text-muted font-monospace"><?= esc(relative_short($a['ended_at'])) ?> · combat #<?= (int) $a['id'] ?></div>
                        </div>
                        <form method="post" action="/attack/<?= (int) $a['attacker_player_id'] ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-dark" title="Attaquer en retour">⚔ Revanche</button>
                        </form>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

</div>

<script>
(function () {
    const btn = document.getElementById('cr-notif-toggle');
    const lbl = document.getElementById('cr-notif-toggle-label');
    if (! btn || typeof Notification === 'undefined') {
        if (btn) btn.style.display = 'none';
        return;
    }

    function render() {
        const enabled = localStorage.getItem('crNotifEnabled') === '1' && Notification.permission === 'granted';
        if (enabled) {
            lbl.textContent = 'Notifications activées ✓';
            btn.classList.add('text-success');
        } else if (Notification.permission === 'denied') {
            lbl.textContent = 'Notifs bloquées (autorise dans le navigateur)';
            btn.classList.add('text-muted');
        } else {
            lbl.textContent = 'Activer notifs';
        }
    }
    render();

    btn.addEventListener('click', async () => {
        if (Notification.permission === 'granted') {
            // Toggle desactivation cote app (la permission browser reste granted).
            const wasEnabled = localStorage.getItem('crNotifEnabled') === '1';
            localStorage.setItem('crNotifEnabled', wasEnabled ? '0' : '1');
            render();
            if (! wasEnabled) location.reload(); // active le polling JS de notifications.php
            return;
        }
        if (Notification.permission === 'denied') {
            alert('Les notifications sont bloquées au niveau navigateur. Autorise-les dans les paramètres de site pour ce domaine.');
            return;
        }
        const perm = await Notification.requestPermission();
        if (perm === 'granted') {
            localStorage.setItem('crNotifEnabled', '1');
            new Notification('Notifications activées', { body: 'Tu seras alerté pour les messages et les attaques.' });
            render();
            location.reload();
        } else {
            render();
        }
    });
})();
</script>

<?= $this->endSection() ?>
