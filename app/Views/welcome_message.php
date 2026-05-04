<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="min-h-[60vh] flex flex-col items-center justify-center text-center">

    <div class="mb-8" x-data="{ glitch: false }" x-init="setInterval(() => { glitch = true; setTimeout(() => glitch = false, 120) }, 4000)">
        <h1 class="text-5xl md:text-7xl font-bold text-neon-pink tracking-tight"
            :class="glitch ? 'opacity-70 translate-x-[2px]' : ''">
            CyberTown
        </h1>
        <p class="mt-3 text-neon-cyan/80 text-sm md:text-base">
            // Le réseau t'attend, runner.
        </p>
    </div>

    <div class="border border-neon-cyan/40 rounded p-6 max-w-xl bg-neon-cyan/5">
        <p class="text-neon-cyan mb-2">&gt; STATUT_SYSTÈME</p>
        <p class="text-neon-green text-sm">[ OK ] CodeIgniter 4 en ligne</p>
        <p class="text-neon-green text-sm">[ OK ] HTMX chargé</p>
        <p class="text-neon-green text-sm">[ OK ] Alpine.js actif</p>
        <p class="text-neon-green text-sm">[ OK ] Tailwind CSS opérationnel</p>
        <p class="text-neon-yellow text-sm mt-2">[ ... ] MVP en construction</p>
    </div>

    <p class="mt-10 text-xs text-neon-cyan/50">
        Connecte-toi bientôt pour rejoindre la grille.
    </p>

</div>

<?= $this->endSection() ?>
