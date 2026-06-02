<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <h1 class="h3 mb-3">Politique de confidentialité</h1>
    <p class="text-muted small mb-4">Dernière mise à jour : <?= date('Y-m-d') ?></p>

    <h2 class="h5 mt-4">1. Responsable du traitement</h2>
    <p>CyberRun est un projet personnel en cours de développement. Pour toute question relative aux données personnelles, contacte l'administrateur via le compte support du jeu.</p>

    <h2 class="h5 mt-4">2. Données collectées</h2>
    <ul>
        <li><strong>Compte</strong> : pseudo, adresse email, mot de passe (haché, jamais en clair).</li>
        <li><strong>Fiche joueur</strong> : niveau, XP, crédits, stats, état (prison, hôpital), statut sexuel/marital (optionnel).</li>
        <li><strong>Personnalisation</strong> : bio, signature, avatar (si fournis).</li>
        <li><strong>Activité</strong> : crimes commis, combats, transferts, messages privés, messages chat, espionnages, achievements, missions, dépôts banque, listings bazaar, primes.</li>
        <li><strong>Relations sociales</strong> : amis, ennemis, cibles, factions, mute.</li>
        <li><strong>Technique</strong> : adresse IP (rate limiting + logs), dates de connexion (streak, online status).</li>
    </ul>

    <h2 class="h5 mt-4">3. Finalités du traitement</h2>
    <ul>
        <li>Fournir le service de jeu (gestion de compte, persistance, sécurité).</li>
        <li>Détecter et prévenir les abus (rate limiting, antiflood chat).</li>
        <li>Permettre les interactions sociales entre joueurs (messages, chat, factions).</li>
        <li>Analyser l'activité (statistiques d'usage, anti-cheat).</li>
    </ul>

    <h2 class="h5 mt-4">4. Base légale</h2>
    <p>Le traitement est fondé sur :</p>
    <ul>
        <li><strong>L'exécution du contrat</strong> entre toi et CyberRun (création de compte = acceptation).</li>
        <li><strong>L'intérêt légitime</strong> pour la sécurité (rate limiting, anti-bot).</li>
        <li><strong>Ton consentement</strong> pour les fonctionnalités optionnelles (avatar, bio, notifications navigateur).</li>
    </ul>

    <h2 class="h5 mt-4">5. Destinataires</h2>
    <p>Les données sont stockées sur l'infrastructure de CyberRun (serveur dédié, base MariaDB). Elles ne sont <strong>pas vendues</strong>, <strong>pas partagées</strong> avec des tiers à des fins commerciales, et ne quittent pas l'infra du jeu — sauf obligation légale (réquisition judiciaire).</p>
    <p>Les autres joueurs peuvent voir tes interactions publiques : pseudo, avatar, signature, bio, niveau, classements, listings bazaar, messages chat.</p>

    <h2 class="h5 mt-4">6. Durée de conservation</h2>
    <ul>
        <li>Compte actif : aussi longtemps que tu utilises le jeu.</li>
        <li>Messages chat : prune automatique des plus anciens (configurable, ~500 par channel).</li>
        <li>Activity logs : conservés indéfiniment pour l'instant (à terme, prune au-delà de 1 an).</li>
        <li>Compte supprimé : <strong>effacement immédiat</strong> de l'intégralité des données via cascade BD.</li>
    </ul>

    <h2 class="h5 mt-4">7. Tes droits (RGPD)</h2>
    <p>Tu peux à tout moment :</p>
    <ul>
        <li><strong>Accéder</strong> à tes données : visibles sur ton profil + export JSON complet via <a href="/profile/data">Mes données</a>.</li>
        <li><strong>Rectifier</strong> tes données : édition profil + personnalisation.</li>
        <li><strong>Supprimer</strong> ton compte : bouton de suppression définitive via <a href="/profile/data">Mes données</a>.</li>
        <li><strong>Récupérer</strong> tes données : export JSON portable (format ouvert, réutilisable).</li>
        <li><strong>T'opposer</strong> à certains traitements : retire ton consentement (avatar, bio, notifications).</li>
        <li>Introduire une réclamation auprès de la <a href="https://www.cnil.fr">CNIL</a> si tu estimes que tes droits ne sont pas respectés.</li>
    </ul>

    <h2 class="h5 mt-4">8. Cookies</h2>
    <p>CyberRun utilise des cookies strictement nécessaires au fonctionnement :</p>
    <ul>
        <li>Cookie de session (authentification).</li>
        <li>Cookie CSRF (protection contre le cross-site request forgery).</li>
    </ul>
    <p>Aucun cookie publicitaire ou de tracking tiers.</p>

    <h2 class="h5 mt-4">9. Sécurité</h2>
    <p>Mots de passe hashés (algo moderne), transactions atomiques sur les écritures sensibles, rate limiting sur les actions critiques, protection CSRF sur les formulaires, filtre sur les uploads.</p>

    <h2 class="h5 mt-4">10. Modifications</h2>
    <p>Cette politique peut être mise à jour. La date de dernière modification figure en haut de cette page.</p>

</div>

<?= $this->endSection() ?>
