<?php

/**
 * Activity log phrases (EN). Mirror of fr/Log.php.
 * Translate freely; placeholders {x} must stay identical (injected from JSON params).
 */

return [
    // ---- Crime ----
    'crime_success'  => 'You succeeded {crime_name} — +{credits} ¢, +{xp} XP, +{cat_xp} XP {cat_name}.',
    'crime_fail'     => 'You failed {crime_name}. Empty pockets.',
    'crime_critical' => 'You critically failed {crime_name}. {destination_label} for {minutes} min.',

    // ---- Train ----
    'train_success'  => 'You trained {stat_name}, +{gain} (−{cost} energy).',

    // ---- Bust / Bail ----
    'bust_success_by_me'    => 'You busted <strong>{target}</strong> out. They are free.',
    'bust_fail_by_me'       => 'Your bust on <strong>{target}</strong> failed — {minutes} min jail for you.',
    'bust_success_on_me'    => '<strong>{author}</strong> busted you out of jail. Free.',
    'bail_paid_by_me'       => 'You paid bail for <strong>{target}</strong> ({cost} ¢).',
    'bail_paid_on_me'       => '<strong>{author}</strong> paid your bail ({cost} ¢). Free.',

    // ---- Mission ----
    'mission_accept' => 'You accepted the mission "{mission_name}" at {fixer_name}.',
    'mission_claim'  => 'You claimed the reward for "{mission_name}" — +{credits} ¢, +{xp} XP.',

    // ---- Progression ----
    'level_up'       => 'You reached level {level}.',

    // ---- Status ----
    'sent_to_jail'      => 'You are sent to jail ({minutes} min).',
    'sent_to_hospital'  => 'You are sent to the cyberclinic ({minutes} min).',
    'released_from_jail'     => 'You are out of jail.',
    'released_from_hospital' => 'You are out of the cyberclinic.',

    // ---- Critical destinations ----
    'destination_jail'     => 'Jail',
    'destination_hospital' => 'Cyberclinic',

    // ---- Money transfers ----
    'transfer_sent'     => 'You sent {amount} ¢ to <strong>{target}</strong>.',
    'transfer_received' => '<strong>{author}</strong> sent you {amount} ¢.',

    // ---- Bounty ----
    'bounty_placed'    => 'You placed a {amount} ¢ bounty on <strong>{target}</strong>.',
    'bounty_claimed'   => 'You collected a {amount} ¢ bounty on <strong>{target}</strong>.',
    'bounty_cancelled' => 'You cancelled a {amount} ¢ bounty (credits refunded).',

    // ---- Relations (friend / enemy / target) ----
    'relation_added'   => 'You added <strong>{target}</strong> to your {type}s.',
    'relation_removed' => 'You removed <strong>{target}</strong> from your {type}s.',

    // ---- Combat ----
    'combat_won_attacker'  => 'You defeated <strong>{target}</strong> in combat.',
    'combat_lost_attacker' => 'You were beaten by <strong>{target}</strong>.',
    'combat_won_defender'  => 'You fought off the attack from <strong>{author}</strong>.',
    'combat_lost_defender' => '<strong>{author}</strong> defeated you in defense.',
    'combat_fled'          => 'You fled from <strong>{target}</strong>.',
    'combat_mug'           => 'You mugged <strong>{target}</strong> for {amount} ¢.',
    'combat_hospitalize'   => 'You hospitalized <strong>{target}</strong> for {minutes} min.',

    // ---- Jobs ----
    'job_worked'   => 'You worked at {job_name} (+{xp} XP, +{credits} ¢).',
    'job_promoted' => 'You got promoted: <strong>{position}</strong> at {job_name}.',
    'job_salary'   => 'You received your salary at {job_name}: +{credits} ¢.',
];
