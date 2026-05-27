<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto py-5" style="max-width: 48rem;">

    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold">CyberRun</h1>
        <p class="text-muted">Jeu navigateur PvP en français.</p>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light small text-uppercase fw-semibold">Statut système</div>
        <ul class="list-group list-group-flush small">
            <li class="list-group-item">CodeIgniter 4 en ligne</li>
            <li class="list-group-item">HTMX chargé</li>
            <li class="list-group-item">Alpine.js actif</li>
            <li class="list-group-item">Bootstrap opérationnel</li>
            <li class="list-group-item text-muted">MVP en construction</li>
        </ul>
    </div>

    <div class="text-center">
        <a href="/register" class="btn btn-dark me-2">Créer un compte</a>
        <a href="/login" class="btn btn-outline-dark">Connexion</a>
    </div>

</div>

<?= $this->endSection() ?>
