<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="min-h-[60vh] flex flex-col items-center justify-center text-center">

    <div class="mb-8" x-data="{ glitch: false }" x-init="setInterval(() => { glitch = true; setTimeout(() => glitch = false, 120) }, 4000)">
        <h1 class="text-5xl md:text-7xl font-bold text-accent tracking-tight"
            :class="glitch ? 'opacity-70 translate-x-[2px]' : ''">
            CyberRun
        </h1>
        <p class="mt-3 text-primary/80 text-sm md:text-base">
            // Le réseau t'attend, runner.
        </p>
    </div>

    <?= view('partials/bloc', [
        'title'      => 'STATUT_SYSTÈME',
        'variant'    => 'primary',
        'extraClass' => 'max-w-xl',
        'slot'       => '<p class="text-success text-sm">[ OK ] CodeIgniter 4 en ligne</p>'
                      . '<p class="text-success text-sm">[ OK ] HTMX chargé</p>'
                      . '<p class="text-success text-sm">[ OK ] Alpine.js actif</p>'
                      . '<p class="text-success text-sm">[ OK ] Tailwind CSS opérationnel</p>'
                      . '<p class="text-warning text-sm mt-2">[ ... ] MVP en construction</p>',
    ]) ?>

    <p class="mt-10 text-xs text-primary/50">
        <a href="/register" class="hover:text-accent transition">&gt; Connecte-toi pour rejoindre la grille</a>
    </p>

</div>

<?= $this->endSection() ?>
