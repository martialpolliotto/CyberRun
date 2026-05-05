<?php
/**
 * Affiche les bonus d'un item en ligne, ex: "+3 FRC +1 RFX".
 *
 * @var array<string,int> $bonuses  ['force'=>3,'blindage'=>0,'reflexes'=>1,'hack'=>0]
 *                                  OU on peut passer item: array{bonus_force,...}
 * @var array<string,int>|null $item Si fourni, lit les bonus_* depuis cet array.
 */

$labels = [
    'force'    => 'FRC',
    'blindage' => 'BLI',
    'reflexes' => 'RFX',
    'hack'     => 'HCK',
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
        $parts[] = '<span class="text-success">' . $sign . $val . ' ' . $code . '</span>';
    }
}
echo $parts === [] ? '<span class="text-primary/30">aucun bonus</span>' : implode(' ', $parts);
