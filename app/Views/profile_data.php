<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-shield-lock"></i> Mes données personnelles</h1>
            <p class="text-muted small mb-0">Conformité RGPD : exporter / supprimer ton compte.</p>
        </div>
        <a href="/profile" class="text-muted text-decoration-none small">‹ retour profil</a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Export -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold">Exporter mes données</div>
        <div class="card-body">
            <p class="small mb-3">
                Télécharge un fichier JSON contenant toutes les données associées à ton compte :
                fiche player, items, missions, messages envoyés / reçus, combats, achievements, dépôts banque,
                etc. Format ouvert, réutilisable.
            </p>
            <a href="/profile/export" class="btn btn-dark"><i class="bi bi-download"></i> Télécharger mes données (JSON)</a>
        </div>
    </div>

    <!-- Suppression -->
    <div class="card border-danger mb-3">
        <div class="card-header bg-danger text-white small text-uppercase fw-semibold">Supprimer mon compte</div>
        <div class="card-body">
            <p class="small mb-2">
                La suppression est <strong>définitive et immédiate</strong>. Toutes tes données sont effacées :
                fiche, inventaire, messages, combats, banque, factions, etc. Tu ne pourras pas récupérer ton pseudo
                ni ton solde.
            </p>
            <p class="small text-muted mb-3">
                Les messages que tu as envoyés à d'autres joueurs disparaitront aussi de leur boîte. Les annonces du bazaar,
                bounties placées, dépôts banque non maturés sont perdus.
            </p>

            <form method="post" action="/profile/delete" class="m-0"
                  onsubmit="return confirm('Dernier avertissement : suppression DÉFINITIVE de tout ton compte. Continuer ?');">
                <?= csrf_field() ?>

                <div class="mb-2">
                    <label class="form-label small">Tape <code>SUPPRIMER</code> pour confirmer</label>
                    <input type="text" name="confirm" required class="form-control" placeholder="SUPPRIMER">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Mot de passe</label>
                    <input type="password" name="password" required class="form-control" autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
            </form>
        </div>
    </div>

    <p class="small text-muted text-center mb-0">
        Voir la <a href="/legal/privacy" class="text-muted">politique de confidentialité</a> pour les détails sur le traitement des données.
    </p>

</div>

<?= $this->endSection() ?>
