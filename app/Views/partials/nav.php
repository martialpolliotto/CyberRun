<?php
/**
 * Navigation principale du header. État conditionnel selon auth.
 */
?>
<nav class="d-flex align-items-center gap-3 small">
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
        <a href="/profile" class="text-dark text-decoration-none">Profil</a>
        <a href="/fixers" class="text-dark text-decoration-none">Fixers</a>
        <a href="/crimes" class="text-dark text-decoration-none">Crimes</a>
        <a href="/lab" class="text-dark text-decoration-none">Lab</a>
        <a href="/equipment" class="text-dark text-decoration-none">Équipement</a>
        <a href="/inventory" class="text-dark text-decoration-none">Inventaire</a>

        <div class="dropdown">
            <button class="btn btn-sm btn-link text-dark text-decoration-none p-0 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Marchés
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/shops">Tous les marchés</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/shop/armurerie">Armurerie</a></li>
                <li><a class="dropdown-item" href="/shop/ripperdoc">Ripperdoc</a></li>
                <li><a class="dropdown-item" href="/shop/friperie">Friperie</a></li>
            </ul>
        </div>

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
