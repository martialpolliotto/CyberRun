<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'CyberRun') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/htmx.org@2.0.3" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        @media (max-width: 991.98px) {
            .cr-sidebar-desktop { display: none; }
        }
        @media (min-width: 992px) {
            .cr-sidebar-offcanvas, .cr-burger { display: none !important; }
        }
        /* Bouton HTMX : spinner pendant la requete (htmx-request ajoutee auto), texte sinon. */
        .cr-htmx-btn .cr-btn-spinner { display: none; }
        .cr-htmx-btn.htmx-request .cr-btn-text { display: none !important; }
        .cr-htmx-btn.htmx-request .cr-btn-spinner { display: inline-block; }

        /* Resultat d'une action : flash de fond colore qui s'estompe sur 2.5s. L'animation
           se rejoue a chaque swap HTMX puisque le DOM est nouveau. */
        @keyframes cr-flash-success { 0% { background-color: rgba(25, 135, 84, 0.30); } 100% { background-color: transparent; } }
        @keyframes cr-flash-danger  { 0% { background-color: rgba(220, 53, 69, 0.30); } 100% { background-color: transparent; } }
        .cr-flash-success { animation: cr-flash-success 2.5s ease-out forwards; border-radius: 6px; }
        .cr-flash-danger  { animation: cr-flash-danger  2.5s ease-out forwards; border-radius: 6px; }

        /* Pendant qu'une nouvelle requete tourne dans la card, masque immediatement
           l'ancien resultat pour que le swap soit visible comme un changement. */
        .card:has(.cr-htmx-btn.htmx-request) .cr-flash-success,
        .card:has(.cr-htmx-btn.htmx-request) .cr-flash-danger { opacity: 0; transition: opacity 150ms; }
    </style>
</head>
<body class="bg-white text-dark">

<?php $isLogged = function_exists('auth') && auth()->loggedIn(); ?>

<!-- Header minimal : logo + burger mobile + admin/logout -->
<header class="border-bottom bg-white sticky-top">
    <div class="container-fluid d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center gap-3">
            <?php if ($isLogged): ?>
                <button class="btn btn-link p-0 text-dark cr-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar-offcanvas" aria-label="Menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
            <?php endif ?>
            <a href="/" class="fs-5 fw-bold text-dark text-decoration-none">CyberRun</a>
        </div>

        <nav class="small">
            <?php if (! $isLogged): ?>
                <a href="/login" class="text-dark text-decoration-none me-3">Connexion</a>
                <a href="/register" class="text-dark text-decoration-none fw-bold">Inscription</a>
            <?php endif ?>
        </nav>
    </div>
</header>

<!-- Layout 2-cols, centré sur grand écran : sidebar permanente + zone principale -->
<div class="mx-auto d-flex" style="max-width: 1380px;">

    <?php if ($isLogged): ?>
        <!-- Sidebar permanente sur desktop -->
        <div class="cr-sidebar-desktop">
            <?= view('partials/sidebar') ?>
        </div>

        <!-- Offcanvas mobile : meme contenu sidebar dans un drawer -->
        <div class="offcanvas offcanvas-start cr-sidebar-offcanvas" tabindex="-1" id="sidebar-offcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title small text-uppercase fw-semibold text-muted mb-0">Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
            </div>
            <div class="offcanvas-body p-0">
                <?= view('partials/sidebar') ?>
            </div>
        </div>
    <?php endif ?>

    <main class="flex-grow-1 px-3 px-md-4 py-4" style="min-width: 0;">
        <?= $this->renderSection('content') ?>
    </main>
</div>

<footer class="border-top mt-5 bg-light">
    <div class="container py-3 text-center small text-muted">
        © 2026 CyberRun — projet en cours de construction
    </div>
</footer>

<?php if ($isLogged): ?>
    <?= view('partials/chat_widget') ?>
<?php endif ?>

</body>
</html>
