<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <span class="small">Toutes les actions sont loguées.</span>
    </div>

    <h1 class="h3 mb-3">Admin — Dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body p-3">
                    <div class="small text-muted text-uppercase">Items total</div>
                    <div class="fs-3 fw-bold mt-1"><?= (int) $stats['items_total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body p-3">
                    <div class="small text-muted text-uppercase">Items actifs</div>
                    <div class="fs-3 fw-bold mt-1"><?= (int) $stats['items_active'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body p-3">
                    <div class="small text-muted text-uppercase">Hors-circuit</div>
                    <div class="fs-3 fw-bold mt-1"><?= (int) $stats['items_discontinued'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body p-3">
                    <div class="small text-muted text-uppercase">Joueurs</div>
                    <div class="fs-3 fw-bold mt-1"><?= (int) $stats['players_total'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="small text-uppercase text-muted mb-2">Outils</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <a href="/admin/items" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Gestion des items</div>
                    <p class="small text-muted mb-0">Créer, éditer, mettre hors-circuit ou supprimer les items du catalogue.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/vendors" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Gestion des marchands</div>
                    <p class="small text-muted mb-0">Portraits, taglines et descriptions des 3 vendeurs.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/fixers" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Gestion des fixers</div>
                    <p class="small text-muted mb-0">PNJ donneurs de missions et ordre de déblocage.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/missions" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Gestion des missions</div>
                    <p class="small text-muted mb-0">Chaînes de missions par fixer, objectifs et récompenses.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/crime-categories" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Catégories de crimes</div>
                    <p class="small text-muted mb-0">Regrouper les crimes (stat dominante, ordre d'affichage).</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/crimes" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Crimes</div>
                    <p class="small text-muted mb-0">Probabilités, récompenses, conséquences critiques, bonus horaire.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/game-settings" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Paramètres du jeu</div>
                    <p class="small text-muted mb-0">Coefficients ajustables (bust, bail, etc.) édités sans toucher au code.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/admin/bots" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Bots</div>
                    <p class="small text-muted mb-0">Joueurs automatisés qui peuplent le monde, agissent selon leur persona à chaque tick.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <div class="card h-100 text-muted">
                <div class="card-body">
                    <div class="fw-bold text-uppercase">Gestion utilisateurs</div>
                    <p class="small mb-0">À venir.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
