<?php
/**
 * Navigation principale du header. État conditionnel selon auth.
 */
?>
<nav class="space-x-4 text-sm">
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
        <a href="/profile" class="hover:text-white transition">Profil</a>
        <span class="text-primary/40">|</span>
        <a href="/lab" class="hover:text-white transition">Lab</a>
        <span class="text-primary/40">|</span>
        <a href="/equipment" class="hover:text-white transition">Équipement</a>
        <span class="text-primary/40">|</span>
        <span class="text-accent"><?= esc(auth()->user()->username) ?></span>
        <a href="/logout" class="text-danger hover:text-red-300 transition">[déconnexion]</a>
    <?php else: ?>
        <a href="/" class="hover:text-white transition">Accueil</a>
        <span class="text-primary/40">|</span>
        <a href="/login" class="hover:text-white transition">Connexion</a>
        <span class="text-primary/40">|</span>
        <a href="/register" class="text-accent hover:text-pink-300 transition">Inscription</a>
    <?php endif; ?>
</nav>
