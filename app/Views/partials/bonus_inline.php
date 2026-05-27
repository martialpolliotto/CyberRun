<?php
/**
 * Affiche les bonus d'un item en ligne, ex: "+3 Force +1 Réflexes".
 *
 * @var array<string,int> $bonuses
 * @var array<string,int>|null $item
 */

$labels = [
    'force'    => 'Force',
    'blindage' => 'Blindage',
    'reflexes' => 'Réflexes',
    'hack'     => 'Hack',
];

if (isset($item)) {
    $bonuses = [
        'force'    => (int) ($item['bonus_force']    ?? 0),
        'blindage' => (int) ($item['bonus_blindage'] ?? 0),
        'reflexes' => (int) ($item['bonus_reflexes'] ?? 0),
        'hack'     => (int) ($item['bonus_hack']     ?? 0),
    ];
} else {
    $bonuses = $bonuses ?? [];
}

$parts = [];
foreach ($labels as $stat => $code) {
    $val = (int) ($bonuses[$stat] ?? 0);
    if ($val !== 0) {
        $sign = $val > 0 ? '+' : '';
        $parts[] = '<span class="fw-semibold">' . $sign . $val . ' ' . $code . '</span>';
    }
}
echo $parts === [] ? '<span class="text-muted">aucun bonus</span>' : implode(' ', $parts);
