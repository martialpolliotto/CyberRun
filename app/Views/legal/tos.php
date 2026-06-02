<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <h1 class="h3 mb-3">Conditions d'utilisation</h1>
    <p class="text-muted small mb-4">Dernière mise à jour : <?= date('Y-m-d') ?></p>

    <h2 class="h5 mt-4">1. Acceptation</h2>
    <p>En créant un compte CyberRun, tu acceptes ces conditions et la <a href="/legal/privacy">politique de confidentialité</a>.</p>

    <h2 class="h5 mt-4">2. Usage du jeu</h2>
    <p>CyberRun est un jeu PvP cyberpunk. Le contenu inclut crimes, combats, espionnage, transferts de crédits virtuels — uniquement dans le cadre du jeu, aucun lien avec la vie réelle.</p>
    <ul>
        <li>Pas d'auto-clicker, de bot externe, de modification client.</li>
        <li>Pas de partage de compte ni de multi-compte non déclaré.</li>
        <li>Pas de propos haineux / racistes / discriminatoires dans le chat ou la signature.</li>
        <li>Pas d'usurpation d'identité d'un autre joueur ou de l'équipe.</li>
        <li>Pas de bug-abuse : signale les bugs au lieu de les exploiter.</li>
    </ul>

    <h2 class="h5 mt-4">3. Modération</h2>
    <p>L'administrateur peut suspendre ou supprimer un compte qui viole ces règles, sans préavis ni remboursement.</p>

    <h2 class="h5 mt-4">4. Données et confidentialité</h2>
    <p>Voir la <a href="/legal/privacy">politique de confidentialité</a> pour le détail du traitement des données et l'exercice de tes droits RGPD.</p>

    <h2 class="h5 mt-4">5. Pas de garantie</h2>
    <p>CyberRun est fourni "tel quel", sans garantie de disponibilité, de performance, ou d'absence de bugs. Les crédits virtuels n'ont aucune valeur monétaire réelle.</p>

    <h2 class="h5 mt-4">6. Suppression de compte</h2>
    <p>Tu peux supprimer ton compte à tout moment via <a href="/profile/data">Mes données</a>. La suppression est définitive.</p>

    <h2 class="h5 mt-4">7. Modifications</h2>
    <p>Ces conditions peuvent évoluer. Les changements seront annoncés via le chat global.</p>

</div>

<?= $this->endSection() ?>
