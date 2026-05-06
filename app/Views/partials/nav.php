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
        <span class="text-primary/40">|</span>
        <div class="inline-block relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" class="hover:text-accent transition">
                Marchés <span class="text-xs">▾</span>
            </button>
            <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-1 w-44 bg-surface-alt border border-line rounded shadow-lg z-50">
                <a href="/shops" class="block px-3 py-2 text-primary hover:bg-stone-100 border-b border-line">Tous les marchés</a>
                <a href="/shop/armurerie" class="block px-3 py-2 text-primary hover:bg-stone-100">Armurerie</a>
                <a href="/shop/ripperdoc" class="block px-3 py-2 text-primary hover:bg-stone-100">Ripperdoc</a>
                <a href="/shop/friperie"  class="block px-3 py-2 text-primary hover:bg-stone-100">Friperie</a>
            </div>
        </div>
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
