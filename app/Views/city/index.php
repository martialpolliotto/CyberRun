<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="mb-4">
        <h1 class="h3 mb-0">Chrome City</h1>
        <p class="text-muted small mb-0">Les lieux du quartier. Tout ce qui bouge passe par là.</p>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <h2 class="small text-uppercase text-muted fw-semibold mb-2">Activités</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="/lab" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold">Le Lab</div>
                    <p class="small text-muted mb-0">Forge ton chrome. Entraîne tes 4 stats contre de l'énergie.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/crimes" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold">Crimes</div>
                    <p class="small text-muted mb-0">Pickpocket, hack, fouille. Crédits + XP, mais attention aux échecs critiques.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/fixers" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold">Fixers</div>
                    <p class="small text-muted mb-0">Les PNJ qui te donnent des missions. Apprentissage et récompenses.</p>
                </div>
            </a>
        </div>
    </div>

    <h2 class="small text-uppercase text-muted fw-semibold mb-2">Commerces</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="/shops" class="card text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="fw-bold">Tous les marchés</div>
                    <p class="small text-muted mb-0">Vue d'ensemble des commerçants du quartier.</p>
                </div>
            </a>
        </div>
        <?php foreach ($vendors as $v): ?>
            <div class="col-md-3">
                <a href="/shop/<?= esc($v['slug']) ?>" class="card text-decoration-none text-dark h-100">
                    <div class="card-body">
                        <div class="fw-bold"><?= esc($v['name']) ?></div>
                        <?php if (! empty($v['tagline'])): ?>
                            <p class="small fst-italic mb-0">« <?= esc($v['tagline']) ?> »</p>
                        <?php endif ?>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <h2 class="small text-uppercase text-muted fw-semibold mb-2">Lieux d'enfermement</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a href="/players/jail" class="card text-decoration-none text-dark h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">Prison</div>
                        <p class="small text-muted mb-0">Les détenus actuellement coffrés.</p>
                    </div>
                    <span class="badge bg-dark"><?= (int) $inmateCount ?></span>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="/players/hospital" class="card text-decoration-none text-dark h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">Cyberclinique</div>
                        <p class="small text-muted mb-0">Les patients en train de se faire rafistoler.</p>
                    </div>
                    <span class="badge bg-secondary"><?= (int) $patientCount ?></span>
                </div>
            </a>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
