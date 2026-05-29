<?php

/**
 * Phrases du log d'activite (FR). Cles utilisees par ActivityLogger via action_key.
 * Placeholders {x} sont injectes depuis le JSON params stocke en base.
 */

return [
    // ---- Crime ----
    'crime_success'  => 'Tu as réussi {crime_name} — +{credits} ¢, +{xp} XP, +{cat_xp} XP {cat_name}.',
    'crime_fail'     => 'Tu as raté {crime_name}. Rien dans les poches.',
    'crime_critical' => 'Tu t\'es planté sur {crime_name}. {destination_label} pour {minutes} min.',

    // ---- Train ----
    'train_success'  => 'Tu as entraîné {stat_name}, +{gain} (−{cost} énergie).',

    // ---- Bust / Bail ----
    'bust_success_by_me'    => 'Tu as bust <strong>{target}</strong>. Il/elle est libre.',
    'bust_fail_by_me'       => 'Tu as raté ton bust sur <strong>{target}</strong> — {minutes} min de prison pour toi.',
    'bust_success_on_me'    => '<strong>{author}</strong> t\'a bust de prison. Libre.',
    'bail_paid_by_me'       => 'Tu as payé la caution de <strong>{target}</strong> pour {cost} ¢.',
    'bail_paid_on_me'       => '<strong>{author}</strong> a payé ta caution ({cost} ¢). Libre.',

    // ---- Mission ----
    'mission_accept' => 'Tu as accepté la mission « {mission_name} » chez {fixer_name}.',
    'mission_claim'  => 'Tu as réclamé la récompense de « {mission_name} » — +{credits} ¢, +{xp} XP.',

    // ---- Progression ----
    'level_up'       => 'Tu as atteint le niveau {level}.',

    // ---- Etat ----
    'sent_to_jail'      => 'Tu es envoyé en prison ({minutes} min).',
    'sent_to_hospital'  => 'Tu es envoyé à la cyberclinique ({minutes} min).',
    'released_from_jail'    => 'Tu es sorti de prison.',
    'released_from_hospital'=> 'Tu es sorti de la cyberclinique.',

    // ---- Destinations critiques (sous-clefs) ----
    'destination_jail'     => 'Prison',
    'destination_hospital' => 'Cyberclinique',

    // ---- Transferts d'argent ----
    'transfer_sent'     => 'Tu as envoyé {amount} ¢ à <strong>{target}</strong>.',
    'transfer_received' => '<strong>{author}</strong> t\'a envoyé {amount} ¢.',

    // ---- Bounty ----
    'bounty_placed'  => 'Tu as placé une prime de {amount} ¢ sur <strong>{target}</strong>.',
    'bounty_claimed' => 'Tu as encaissé une prime de {amount} ¢ sur la tête de <strong>{target}</strong>.',

    // ---- Relations (ami / ennemi / cible) ----
    'relation_added'   => 'Tu as ajouté <strong>{target}</strong> à tes {type}s.',
    'relation_removed' => 'Tu as retiré <strong>{target}</strong> de tes {type}s.',

    // ---- Combat ----
    'combat_won_attacker'  => 'Tu as battu <strong>{target}</strong> au combat.',
    'combat_lost_attacker' => 'Tu as été battu par <strong>{target}</strong>.',
    'combat_won_defender'  => 'Tu as repoussé l\'attaque de <strong>{author}</strong>.',
    'combat_lost_defender' => '<strong>{author}</strong> t\'a battu en défense.',
    'combat_fled'          => 'Tu as fui face à <strong>{target}</strong>.',
    'combat_mug'           => 'Tu as dépouillé <strong>{target}</strong> de {amount} ¢.',
    'combat_hospitalize'   => 'Tu as envoyé <strong>{target}</strong> à la cyberclinique pour {minutes} min.',

    // ---- Jobs ----
    'job_worked'   => 'Tu as bossé à {job_name} (+{xp} XP, +{credits} ¢).',
    'job_promoted' => 'Tu as été promu : <strong>{position}</strong> chez {job_name}.',
    'job_salary'   => 'Tu as touché ton salaire chez {job_name} : +{credits} ¢.',
];
