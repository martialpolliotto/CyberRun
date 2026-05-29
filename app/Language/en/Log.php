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
    'bounty_placed'  => 'You placed a {amount} ¢ bounty on <strong>{target}</strong>.',
    'bounty_claimed' => 'You collected a {amount} ¢ bounty on <strong>{target}</strong>.',

    // ---- Relations (friend / enemy / target) ----
    'relation_added'   => 'You added <strong>{target}</strong> to your {type}s.',
    'relation_removed' => 'You removed <strong>{target}</strong> from your {type}s.',
];
