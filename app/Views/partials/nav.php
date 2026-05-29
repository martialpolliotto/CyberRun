<?php
/**
 * Navigation principale du header. État conditionnel selon auth.
 */
?>
<nav class="d-flex align-items-center gap-3 small">
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
        <a href="/profile" class="text-dark text-decoration-none">Profil</a>
        <a href="/log" class="text-dark text-decoration-none">Log</a>
        <a href="/city" class="text-dark text-decoration-none fw-semibold">Chrome City</a>
        <a href="/equipment" class="text-dark text-decoration-none">Équipement</a>
        <a href="/inventory" class="text-dark text-decoration-none">Inventaire</a>
        <a href="/players" class="text-dark text-decoration-none">Joueurs</a>
        <a href="/leaderboards" class="text-dark text-decoration-none">Classements</a>

        <?php if (auth()->user()->inGroup('admin', 'superadmin')): ?>
            <a href="/admin" class="text-dark text-decoration-none fw-bold">[ADMIN]</a>
        <?php endif ?>

        <span class="text-muted">|</span>
        <span class="fw-bold"><?= esc(auth()->user()->username) ?></span>
        <a href="/logout" class="text-muted text-decoration-none">[déconnexion]</a>
    <?php else: ?>
        <a href="/" class="text-dark text-decoration-none">Accueil</a>
        <a href="/login" class="text-dark text-decoration-none">Connexion</a>
        <a href="/register" class="text-dark text-decoration-none fw-bold">Inscription</a>
    <?php endif; ?>
</nav>
