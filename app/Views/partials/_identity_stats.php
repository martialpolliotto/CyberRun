<?php
/**
 * Bloc identite dynamique de la sidebar : solde + niveau + streak.
 * Pseudo n'est pas la-dedans car il ne change jamais.
 *
 * Cible OOB pour les HTMX qui modifient credits/level/streak (crime success,
 * lab, admin bar, level-up, etc.).
 *
 * @var array $player
 * @var bool  $oob (optional) ajoute hx-swap-oob="true"
 */
helper('number');
$canLevelUp = (int) $player['xp'] >= (int) $player['level'] * 100;
?>
<div id="cr-identity-stats" class="small mb-3"<?= !empty($oob) ? ' hx-swap-oob="true"' : '' ?>>
    <div class="d-flex">
        <span class="text-muted" style="width: 5rem;">Solde</span>
        <span class="fw-bold font-monospace">¢<?= number_format((int) $player['credits']) ?></span>
    </div>
    <div class="d-flex">
        <span class="text-muted" style="width: 5rem;">Niveau</span>
        <span class="fw-bold font-monospace">
            <?= (int) $player['level'] ?>
            <?php if ($canLevelUp): ?>
                <a href="/level-up" class="badge bg-dark text-decoration-none ms-1" title="Passage de niveau disponible">↑ dispo</a>
            <?php endif ?>
        </span>
    </div>
    <?php if ((int) $player['login_streak_days'] > 0): ?>
        <div class="d-flex" title="Connexion <?= (int) $player['login_streak_days'] ?> jours d'affilée">
            <span class="text-muted" style="width: 5rem;">Streak</span>
            <span class="fw-bold font-monospace text-warning"><i class="bi bi-fire"></i> <?= (int) $player['login_streak_days'] ?> j</span>
        </div>
    <?php endif ?>
</div>
