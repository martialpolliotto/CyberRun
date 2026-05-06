<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-3xl mx-auto py-10 space-y-8">

    <div class="text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-primary">CyberRun</h1>
        <p class="mt-2 text-muted">Jeu navigateur PvP cyberpunk en français.</p>
    </div>

    <?= view('partials/bloc', [
        'title'      => 'Statut système',
        'variant'    => 'primary',
        'slot'       => '<ul class="text-sm space-y-1">'
                      . '<li class="text-success">✓ CodeIgniter 4 en ligne</li>'
                      . '<li class="text-success">✓ HTMX chargé</li>'
                      . '<li class="text-success">✓ Alpine.js actif</li>'
                      . '<li class="text-success">✓ Tailwind CSS opérationnel</li>'
                      . '<li class="text-warning mt-2">⏳ MVP en construction</li>'
                      . '</ul>',
    ]) ?>

    <div class="text-center space-x-3">
        <a href="/register" class="inline-block px-5 py-2 bg-accent text-white font-medium rounded hover:bg-sky-800 transition">
            Créer un compte
        </a>
        <a href="/login" class="inline-block px-5 py-2 border border-line text-primary font-medium rounded hover:bg-stone-200 transition">
            Connexion
        </a>
    </div>

</div>

<?= $this->endSection() ?>
