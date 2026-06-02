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
    <script>
        // Applique la pref sidebar AVANT le rendu pour eviter le flash (FOUC).
        (function(){ if (localStorage.getItem('crSidebarCollapsed') === '1') document.documentElement.classList.add('cr-sidebar-collapsed'); })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        /* Le burger ne s'affiche que sur mobile (la sidebar est inline sur desktop via offcanvas-lg). */
        @media (min-width: 992px) {
            .cr-burger { display: none !important; }
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

        /* Jauges ressources style Torn : couleur par type + crans toutes les 10%.
           Le track Bootstrap garde son bg gris, la barre remplie prend la couleur,
           et un overlay ::after dessine les crans au-dessus des deux portions. */
        .cr-bar-life      { background-color: #dc3545 !important; } /* rouge   */
        .cr-bar-energy    { background-color: #198754 !important; } /* vert    */
        .cr-bar-nerve     { background-color: #fd7e14 !important; } /* orange  */
        .cr-bar-xp        { background-color: #0dcaf0 !important; } /* cyan    */
        .cr-bar-addiction { background-color: #a52a2a !important; } /* brun    */
        .cr-bar-mission   { background-color: #6c757d !important; } /* gris    */

        .cr-bar-notched { position: relative; }
        .cr-bar-notched::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                to right,
                transparent 0,
                transparent calc(10% - 1px),
                rgba(0, 0, 0, 0.28) calc(10% - 1px),
                rgba(0, 0, 0, 0.28) 10%
            );
            pointer-events: none;
        }

        /* Sidebar collapsable : toggle desktop only (sur mobile c'est deja offcanvas-lg).
           Quand html.cr-sidebar-collapsed, la sidebar disparait du flex flow → main
           prend toute la largeur (utile pour les pages denses : admin logs, etc.).
           On cible <html> et non <body> pour pouvoir appliquer la classe AVANT le
           rendu du body et eviter le FOUC. */
        @media (min-width: 992px) {
            html.cr-sidebar-collapsed .cr-sidebar-desktop { display: none !important; }
        }
        .cr-sidebar-toggle { background: none; border: none; padding: 0; }
        .cr-sidebar-toggle:hover { color: #6c757d; }
    </style>
</head>
<body class="bg-white text-dark">

<?php $isLogged = function_exists('auth') && auth()->loggedIn(); ?>

<!-- Header minimal : logo + burger mobile + admin/logout -->
<!-- max-width identique au layout principal pour que le contenu reste aligne sur les grands ecrans -->
<header class="border-bottom bg-white sticky-top">
    <div class="mx-auto d-flex align-items-center justify-content-between px-3 py-2" style="max-width: 1380px;">
        <div class="d-flex align-items-center gap-3">
            <?php if ($isLogged): ?>
                <!-- Mobile : ouvre l'offcanvas-lg. Desktop : toggle collapse de la sidebar. -->
                <button class="btn btn-link p-0 text-dark cr-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar-offcanvas" aria-label="Menu (mobile)">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <button class="d-none d-lg-inline-flex cr-sidebar-toggle text-dark"
                        type="button" id="cr-sidebar-toggle-btn"
                        aria-label="Masquer/afficher la sidebar"
                        title="Masquer/afficher la sidebar">
                    <i class="bi bi-layout-sidebar fs-4"></i>
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
        <?php
            // Rend la sidebar UNE SEULE fois (avant: rendue 2x = 2 findByUserId + 2 unreadCount par page).
            // Bootstrap offcanvas-lg permet d'afficher le meme noeud comme sidebar fixe desktop
            // ET comme drawer offcanvas mobile via une seule directive responsive.
            $sidebarHtml = view('partials/sidebar');
        ?>
        <div class="offcanvas-lg offcanvas-start cr-sidebar-desktop" tabindex="-1" id="sidebar-offcanvas" aria-labelledby="sidebar-offcanvas-title">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title small text-uppercase fw-semibold text-muted mb-0" id="sidebar-offcanvas-title">Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar-offcanvas" aria-label="Fermer"></button>
            </div>
            <div class="offcanvas-body p-0">
                <?= $sidebarHtml ?>
            </div>
        </div>
    <?php endif ?>

    <main class="flex-grow-1 px-3 px-md-4 py-4" style="min-width: 0;">
        <?= $this->renderSection('content') ?>
    </main>
</div>

<footer class="border-top mt-5 bg-light">
    <div class="container py-3 text-center small text-muted">
        © 2026 CyberRun — projet en cours de construction ·
        <a href="/legal/privacy" class="text-muted">Confidentialité</a> ·
        <a href="/legal/tos" class="text-muted">CGU</a>
    </div>
</footer>

<?php if ($isLogged): ?>
    <?= view('partials/admin_bar') ?>
    <?= view('partials/chat_widget') ?>
    <?= view('partials/tutorial') ?>
    <?= view('partials/notifications') ?>
    <script>
        (function(){
            const btn = document.getElementById('cr-sidebar-toggle-btn');
            if (! btn) return;
            btn.addEventListener('click', () => {
                const collapsed = document.documentElement.classList.toggle('cr-sidebar-collapsed');
                localStorage.setItem('crSidebarCollapsed', collapsed ? '1' : '0');
            });
        })();
    </script>
<?php endif ?>

</body>
</html>
