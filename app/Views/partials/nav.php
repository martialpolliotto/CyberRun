<?php
/**
 * Navigation principale du header. État conditionnel selon auth.
 */
?>
<nav class="space-x-4 text-sm">
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
        <a href="/profile" class="hover:text-accent transition">Profil</a>
        <span class="text-primary/40">|</span>
        <a href="/lab" class="hover:text-accent transition">Lab</a>
        <span class="text-primary/40">|</span>
        <a href="/equipment" class="hover:text-accent transition">Équipement</a>
        <?php if (auth()->user()->inGroup('admin', 'superadmin')): ?>
            <span class="text-primary/40">|</span>
            <a href="/admin" class="text-warning hover:text-orange-300 transition">[ADMIN]</a>
        <?php endif ?>
        <span class="text-primary/40">|</span>
        <span class="text-accent"><?= esc(auth()->user()->username) ?></span>
        <a href="/logout" class="text-danger hover:text-red-300 transition">[déconnexion]</a>
    <?php else: ?>
        <a href="/" class="hover:text-accent transition">Accueil</a>
        <span class="text-primary/40">|</span>
        <a href="/login" class="hover:text-accent transition">Connexion</a>
        <span class="text-primary/40">|</span>
        <a href="/register" class="text-accent hover:text-pink-300 transition">Inscription</a>
    <?php endif; ?>
</nav>
