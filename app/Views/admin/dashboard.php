<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-5xl mx-auto space-y-4">

    <!-- Bandeau ADMIN -->
    <div class="border border-warning/60 bg-warning/10 px-4 py-2 flex items-center gap-3">
        <span class="text-warning font-bold uppercase tracking-widest">[ ADMIN ]</span>
        <span class="text-warning/80 text-sm">// Tu es dans la zone administration. Toutes les actions sont loguées.</span>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold text-warning">&gt; ADMIN_DASHBOARD</h1>

    <!-- Stats globales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="border border-primary/30 bg-black/40 p-3 text-center">
            <p class="text-primary/70 text-xs uppercase tracking-wider">Items total</p>
            <p class="text-3xl text-white font-bold mt-1"><?= (int) $stats['items_total'] ?></p>
        </div>
        <div class="border border-success/30 bg-black/40 p-3 text-center">
            <p class="text-success/70 text-xs uppercase tracking-wider">Items actifs</p>
            <p class="text-3xl text-white font-bold mt-1"><?= (int) $stats['items_active'] ?></p>
        </div>
        <div class="border border-warning/30 bg-black/40 p-3 text-center">
            <p class="text-warning/70 text-xs uppercase tracking-wider">Hors-circuit</p>
            <p class="text-3xl text-white font-bold mt-1"><?= (int) $stats['items_discontinued'] ?></p>
        </div>
        <div class="border border-primary/30 bg-black/40 p-3 text-center">
            <p class="text-primary/70 text-xs uppercase tracking-wider">Joueurs</p>
            <p class="text-3xl text-white font-bold mt-1"><?= (int) $stats['players_total'] ?></p>
        </div>
    </div>

    <!-- Outils admin -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; OUTILS</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <a href="/admin/items" class="block border border-warning/40 bg-warning/5 p-4 hover:border-accent/60 hover:bg-warning/10 transition">
                <p class="text-warning font-bold uppercase tracking-wider">Gestion des items</p>
                <p class="text-primary/70 text-sm mt-1">Créer, éditer, mettre hors-circuit ou supprimer définitivement les items du catalogue.</p>
            </a>
            <div class="block border border-primary/20 bg-black/30 p-4 opacity-60">
                <p class="text-primary/40 font-bold uppercase tracking-wider">Gestion utilisateurs</p>
                <p class="text-primary/40 text-sm mt-1">À venir.</p>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
