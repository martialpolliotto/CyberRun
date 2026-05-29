<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlayerModel;
use CodeIgniter\Database\RawSql;

/**
 * Outils admin pour ajuster rapidement les stats de l'admin connecte (debug / playtest).
 *
 * Whitelist stricte des champs ajustables, clamp manuel pour les ressources avec max.
 * Permet aussi le "set to max" et "set to 0".
 */
class PlayerTools extends BaseController
{
    /**
     * Champs ajustables. Cle = field BDD. Valeurs : label, max_field (null si pas de cap haut),
     * floor (default 0).
     *
     * @var array<string, array{label: string, max_field: ?string, group: string}>
     */
    public const FIELDS = [
        // Ressources avec max
        'hp_current'         => ['label' => 'Life',            'max_field' => 'hp_max',     'group' => 'ressources'],
        'energy_current'     => ['label' => 'Énergie',         'max_field' => 'energy_max', 'group' => 'ressources'],
        'nerve_current'      => ['label' => 'Nerve',           'max_field' => 'nerve_max',  'group' => 'ressources'],
        // Économie / progression
        'credits'            => ['label' => 'Crédits',         'max_field' => null,         'group' => 'progression'],
        'xp'                 => ['label' => 'XP joueur',       'max_field' => null,         'group' => 'progression'],
        'level'              => ['label' => 'Niveau',          'max_field' => null,         'group' => 'progression'],
        // Stats combat
        'stat_force'         => ['label' => 'Force',           'max_field' => null,         'group' => 'combat'],
        'stat_blindage'      => ['label' => 'Blindage',        'max_field' => null,         'group' => 'combat'],
        'stat_reflexes'      => ['label' => 'Réflexes',        'max_field' => null,         'group' => 'combat'],
        'stat_hack'          => ['label' => 'Hack',            'max_field' => null,         'group' => 'combat'],
        // Stats job
        'job_xp'             => ['label' => 'XP job',          'max_field' => null,         'group' => 'job'],
        'job_stat_tech'      => ['label' => 'Tech',            'max_field' => null,         'group' => 'job'],
        'job_stat_endurance' => ['label' => 'Endurance',       'max_field' => null,         'group' => 'job'],
        'job_stat_charisme'  => ['label' => 'Charisme',        'max_field' => null,         'group' => 'job'],
        // Etat
        'addiction_level'    => ['label' => 'Addiction',       'max_field' => null,         'group' => 'etat'],
    ];

    public const GROUP_LABELS = [
        'ressources'  => 'Ressources',
        'progression' => 'Progression',
        'combat'      => 'Stats combat',
        'job'         => 'Stats job',
        'etat'        => 'État',
    ];

    public function index()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/admin')->with('error', 'Fiche player introuvable.');
        }

        // Groupe les champs par categorie pour l'affichage.
        $byGroup = [];
        foreach (self::FIELDS as $field => $cfg) {
            $byGroup[$cfg['group']][] = $field;
        }

        return view('admin/player_tools/index', [
            'me'            => $me,
            'fields'        => self::FIELDS,
            'fields_by_group' => $byGroup,
            'group_labels'  => self::GROUP_LABELS,
        ]);
    }

    /**
     * Applique un ajustement (delta) ou un set au max / a 0 sur un champ donne.
     * action = 'delta' (utilise param delta int) | 'max' | 'zero'
     */
    public function adjust()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/admin')->with('error', 'Fiche player introuvable.');
        }

        $field  = (string) $this->request->getPost('field');
        $action = (string) $this->request->getPost('action');

        if (! isset(self::FIELDS[$field])) {
            return redirect()->to('/admin/player-tools')->with('error', 'Champ non autorise.');
        }

        $cfg = self::FIELDS[$field];
        $me  = model(PlayerModel::class)->find((int) $me['id']);

        $newValue = null;
        if ($action === 'max') {
            $newValue = $cfg['max_field'] !== null ? (int) $me[$cfg['max_field']] : 999999;
        } elseif ($action === 'zero') {
            $newValue = 0;
        } elseif ($action === 'delta') {
            $delta = (int) $this->request->getPost('delta');
            $current = (int) $me[$field];
            $newValue = max(0, $current + $delta);
            if ($cfg['max_field'] !== null) {
                $newValue = min((int) $me[$cfg['max_field']], $newValue);
            }
        } else {
            return redirect()->to('/admin/player-tools')->with('error', 'Action invalide.');
        }

        model(PlayerModel::class)->update((int) $me['id'], [
            $field => $newValue,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/player-tools')
            ->with('message', $cfg['label'] . ' → ' . number_format($newValue));
    }
}
