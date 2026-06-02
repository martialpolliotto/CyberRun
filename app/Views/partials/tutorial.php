<?php
/**
 * Tutorial overlay : tour guide les nouveaux joueurs.
 *
 * Trigger : 1ere connexion (localStorage crTutorialDone !== '1').
 * Sauter ou completer pose crTutorialDone='1' -> ne reapparait plus.
 * Reentry : bouton 'Refaire le tutoriel' sur /profile vide la cle et rafraichit.
 *
 * Aucune dependance externe : CSS + JS self-contained. Pas de Bootstrap modal
 * (incompatible avec le spotlight cut-out, et plus de poids JS).
 */

if (! function_exists('auth') || ! auth()->loggedIn()) return;
?>

<style>
.cr-tour-backdrop {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(0, 0, 0, 0.6);
    pointer-events: auto;
}
.cr-tour-spotlight {
    position: fixed; z-index: 2001;
    border: 2px solid #fff;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6),
                0 0 20px rgba(255, 255, 255, 0.4);
    border-radius: 6px;
    pointer-events: none;
    transition: top 0.25s ease, left 0.25s ease, width 0.25s ease, height 0.25s ease;
}
.cr-tour-card {
    position: fixed; z-index: 2002;
    background: #fff;
    border: 1px solid #212529;
    border-radius: 6px;
    padding: 1rem 1.2rem;
    max-width: 22rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    transition: top 0.25s ease, left 0.25s ease;
}
.cr-tour-card h3 { font-size: 1.1rem; margin: 0 0 0.5rem 0; font-weight: 700; }
.cr-tour-card p  { font-size: 0.9rem; margin: 0 0 0.8rem 0; line-height: 1.4; }
.cr-tour-footer { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.cr-tour-step-counter { font-size: 0.75rem; color: #6c757d; font-family: monospace; }
.cr-tour-actions { display: flex; gap: 0.4rem; }
.cr-tour-btn {
    border: 1px solid #212529; background: #fff; color: #212529;
    padding: 0.3rem 0.7rem; font-size: 0.85rem; cursor: pointer;
    border-radius: 4px;
}
.cr-tour-btn.primary { background: #212529; color: #fff; }
.cr-tour-btn:hover { opacity: 0.85; }
.cr-tour-skip { font-size: 0.75rem; color: #6c757d; text-decoration: underline; cursor: pointer; background: none; border: none; padding: 0; }
</style>

<div id="cr-tour" style="display: none;">
    <div class="cr-tour-backdrop"></div>
    <div class="cr-tour-spotlight"></div>
    <div class="cr-tour-card">
        <h3 id="cr-tour-title"></h3>
        <p  id="cr-tour-body"></p>
        <div class="cr-tour-footer">
            <span class="cr-tour-step-counter" id="cr-tour-counter"></span>
            <div class="cr-tour-actions">
                <button class="cr-tour-skip"  id="cr-tour-skip">Sauter</button>
                <button class="cr-tour-btn"   id="cr-tour-prev">‹ Précédent</button>
                <button class="cr-tour-btn primary" id="cr-tour-next">Suivant ›</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Active seulement si jamais affiche ou si on a explicitement demande un restart.
    const STORAGE_KEY = 'crTutorialDone';
    if (localStorage.getItem(STORAGE_KEY) === '1' && ! window.crForceTour) return;

    const steps = [
        { selector: null,              title: 'Bienvenue à Night City',
          body: 'CyberRun est un jeu PvP cyberpunk. Tu vas commettre des crimes, taper d\'autres joueurs, monter une faction, et survivre. Petit tour des outils ?' },
        { selector: '[data-tour="profile"]', title: 'Ton profil',
          body: 'Stats, identité, customisation. Bio + signature + avatar éditables.' },
        { selector: '[data-tour="crimes"]',  title: 'Crimes',
          body: 'Ta source #1 de revenus. Chaque crime coûte de la NRV. Plus tu en commets, plus tu débloques les paliers de catégorie.' },
        { selector: '[data-tour="lab"]',     title: 'Le Lab',
          body: 'Entraîne tes 4 stats combat (Force, Blindage, Réflexes, Hack). Coût en énergie.' },
        { selector: '[data-tour="jobs"]',    title: 'Jobs',
          body: 'Prends un job, tu touches un salaire passif chaque jour + des stats de métier. Pas besoin d\'y revenir tous les jours.' },
        { selector: '[data-tour="bazaar"]',  title: 'Bazaar',
          body: 'Marché entre joueurs. Vends tes loots, achète les bons coups. Fee de 5% côté vendeur.' },
        { selector: '[data-tour="faction"]', title: 'Factions',
          body: 'Rejoins un gang ou crée le tien (cost 100k¢ + niv 5). Vous pouvez vous déclarer la guerre.' },
        { selector: '[data-tour="dailies"]', title: 'Dailies',
          body: '3 défis par jour, mêmes pour tout le monde. Réclame les rewards quand complets.' },
        { selector: '[data-tour="chat"]',    title: 'Chat live',
          body: 'Bottom-right en bas de l\'écran. 4 channels publics + faction. Antiflood actif, liens externes interdits.' },
        { selector: '[data-tour="wiki"]',    title: 'Le Wiki',
          body: 'Toutes les mécaniques détaillées ici. Reviens-y dès que tu doutes.' },
    ];

    let idx = 0;
    const tour       = document.getElementById('cr-tour');
    const spotlight  = tour.querySelector('.cr-tour-spotlight');
    const card       = tour.querySelector('.cr-tour-card');
    const title      = document.getElementById('cr-tour-title');
    const body       = document.getElementById('cr-tour-body');
    const counter    = document.getElementById('cr-tour-counter');
    const prevBtn    = document.getElementById('cr-tour-prev');
    const nextBtn    = document.getElementById('cr-tour-next');
    const skipBtn    = document.getElementById('cr-tour-skip');

    function renderStep() {
        const step = steps[idx];
        title.textContent = step.title;
        body.textContent  = step.body;
        counter.textContent = (idx + 1) + ' / ' + steps.length;
        prevBtn.style.visibility = idx === 0 ? 'hidden' : 'visible';
        nextBtn.textContent = idx === steps.length - 1 ? 'Terminer ✓' : 'Suivant ›';

        if (step.selector) {
            const target = document.querySelector(step.selector);
            if (target) {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                const r = target.getBoundingClientRect();
                const pad = 6;
                spotlight.style.top    = (r.top  - pad) + 'px';
                spotlight.style.left   = (r.left - pad) + 'px';
                spotlight.style.width  = (r.width  + pad * 2) + 'px';
                spotlight.style.height = (r.height + pad * 2) + 'px';
                spotlight.style.display = '';
                // Place la carte a cote ou en dessous selon l'espace dispo.
                const cardWidth  = 22 * 16; // px
                const cardHeight = card.offsetHeight || 160;
                let left = r.right + 16;
                if (left + cardWidth > window.innerWidth - 16) left = Math.max(16, r.left - cardWidth - 16);
                let top  = r.top;
                if (top + cardHeight > window.innerHeight - 16) top = window.innerHeight - cardHeight - 16;
                if (top < 16) top = 16;
                card.style.top  = top + 'px';
                card.style.left = left + 'px';
                return;
            }
        }
        // Centre l'overlay (1ere etape sans target ou target manquante).
        spotlight.style.display = 'none';
        card.style.top  = '50%';
        card.style.left = '50%';
        card.style.transform = 'translate(-50%, -50%)';
        setTimeout(() => { card.style.transform = ''; }, 10); // reset apres place
        // En realite il faut un translate persistent : on l'enleve apres pour les steps suivants.
    }

    function open() {
        tour.style.display = '';
        idx = 0;
        renderStep();
    }
    function close() {
        tour.style.display = 'none';
        localStorage.setItem(STORAGE_KEY, '1');
        window.crForceTour = false;
    }

    prevBtn.addEventListener('click', () => { if (idx > 0) { idx--; renderStep(); } });
    nextBtn.addEventListener('click', () => {
        if (idx < steps.length - 1) { idx++; renderStep(); } else { close(); }
    });
    skipBtn.addEventListener('click', close);

    // Re-position au resize.
    window.addEventListener('resize', () => { if (tour.style.display !== 'none') renderStep(); });

    open();
})();
</script>
