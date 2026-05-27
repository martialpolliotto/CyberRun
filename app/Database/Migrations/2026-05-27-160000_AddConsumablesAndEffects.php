<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Systeme de consommables a la Torn : boosters (petits bonus, peu de risques) et
 * drogues (gros bonus, risque overdose + dependance qui s'accumule).
 *
 * - items recoit consumable_type + cooldown + effets (HP/NRG/NRV instant,
 *   stats temporaires, stats max temporaires) + addiction + overdose
 * - players recoit addiction_level, addiction_updated_at (pour decay lazy),
 *   last_booster_at, last_drug_at (pour cooldown par categorie)
 * - player_active_effects stocke les effets temporaires en cours
 *   (un seul effet par kind : 1 booster + 1 drogue max simultanement)
 *
 * Regle : un item avec consumable_type != null ne peut PAS etre equipe.
 * Le slot reste rempli (contrainte schema) mais Equipment::index filtre out.
 *
 * Seed : 4 consommables minimes pour pouvoir tester (2 boosters au Ripperdoc,
 * 2 drogues a la Friperie - elle "couvre" aussi le commerce moins propre).
 */
class AddConsumablesAndEffects extends Migration
{
    public function up()
    {
        // Idempotent : on ne ajoute que les colonnes/tables manquantes. Permet de re-run
        // si une execution precedente a echoue en plein milieu (bug de premier jet).

        // ---- ALTER items : champs consommables ----
        $itemColumns = [
            'consumable_type'              => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'after' => 'slot'],
            'cooldown_seconds'             => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'consumable_type'],
            'effect_hp'                    => ['type' => 'INT', 'default' => 0, 'after' => 'cooldown_seconds'],
            'effect_nrg'                   => ['type' => 'INT', 'default' => 0, 'after' => 'effect_hp'],
            'effect_nrv'                   => ['type' => 'INT', 'default' => 0, 'after' => 'effect_nrg'],
            'effect_force'                 => ['type' => 'INT', 'default' => 0, 'after' => 'effect_nrv'],
            'effect_blindage'              => ['type' => 'INT', 'default' => 0, 'after' => 'effect_force'],
            'effect_reflexes'              => ['type' => 'INT', 'default' => 0, 'after' => 'effect_blindage'],
            'effect_hack'                  => ['type' => 'INT', 'default' => 0, 'after' => 'effect_reflexes'],
            'effect_hp_max'                => ['type' => 'INT', 'default' => 0, 'after' => 'effect_hack'],
            'effect_nrg_max'               => ['type' => 'INT', 'default' => 0, 'after' => 'effect_hp_max'],
            'effect_nrv_max'               => ['type' => 'INT', 'default' => 0, 'after' => 'effect_nrg_max'],
            'effect_duration_seconds'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'effect_nrv_max'],
            'addiction_threshold_increase' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'effect_duration_seconds'],
            'overdose_chance_pct'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'addiction_threshold_increase'],
            'overdose_hospital_min'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'overdose_chance_pct'],
            'overdose_hospital_max'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'overdose_hospital_min'],
        ];
        foreach ($itemColumns as $col => $def) {
            if (! $this->db->fieldExists($col, 'items')) {
                $this->forge->addColumn('items', [$col => $def]);
            }
        }

        // ---- ALTER players ----
        $playerColumns = [
            'addiction_level'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'in_jail_until'],
            'addiction_updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'addiction_level'],
            'last_booster_at'      => ['type' => 'DATETIME', 'null' => true, 'after' => 'addiction_updated_at'],
            'last_drug_at'         => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_booster_at'],
        ];
        foreach ($playerColumns as $col => $def) {
            if (! $this->db->fieldExists($col, 'players')) {
                $this->forge->addColumn('players', [$col => $def]);
            }
        }

        // ---- player_active_effects ----
        if (! $this->db->tableExists('player_active_effects')) {
            $this->forge->addField([
                'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
                'kind'       => ['type' => 'VARCHAR', 'constraint' => 16],
                'item_id'    => ['type' => 'BIGINT', 'unsigned' => true],
                'started_at' => ['type' => 'DATETIME', 'null' => true],
                'expires_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['player_id', 'kind']);
            $this->forge->addKey(['player_id', 'expires_at']);
            $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('item_id', 'items', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('player_active_effects');
        }

        // ---- Seed : items consommables ----
        $vendors = $this->db->table('vendors')->select('id, slug')->get()->getResultArray();
        $vendorId = array_column($vendors, 'id', 'slug');

        $now = date('Y-m-d H:i:s');

        // Slot reste rempli (contrainte), on utilise un slot existant arbitrairement (cyberdeck).
        // Le filtrage equipment se fait via consumable_type != null.
        $consumables = [
            // ---- Boosters ----
            [
                'slug' => 'cnsm-energy-drink', 'name' => 'Energy Drink',
                'description' => 'Cocktail de cafeine et taurine. Boost la nerve, rien de mechant.',
                'slot' => 'cyberdeck', 'consumable_type' => 'booster',
                'cooldown_seconds' => 600,
                'effect_nrv' => 15,
                'vendor_id' => $vendorId['ripperdoc'] ?? null, 'price' => 50,
            ],
            [
                'slug' => 'cnsm-medkit', 'name' => 'Trousse de premiers soins',
                'description' => 'Bandages, antiseptique, nano-patches. Repare l\'essentiel.',
                'slot' => 'cyberdeck', 'consumable_type' => 'booster',
                'cooldown_seconds' => 1800,
                'effect_hp' => 40,
                'vendor_id' => $vendorId['ripperdoc'] ?? null, 'price' => 150,
            ],

            // ---- Drogues ----
            [
                'slug' => 'cnsm-stim-combat', 'name' => 'Stim de combat',
                'description' => 'Adrenaline synthese, dilatation des temps de reaction. Force et reflexes booster, mais ca arrache.',
                'slot' => 'cyberdeck', 'consumable_type' => 'drug',
                'cooldown_seconds' => 3600,
                'effect_force' => 5, 'effect_reflexes' => 5,
                'effect_duration_seconds' => 900,
                'addiction_threshold_increase' => 10,
                'overdose_chance_pct' => 5,
                'overdose_hospital_min' => 10, 'overdose_hospital_max' => 25,
                'vendor_id' => $vendorId['friperie'] ?? null, 'price' => 200,
            ],
            [
                'slug' => 'cnsm-neuro-spike', 'name' => 'Neuro-spike',
                'description' => 'Implant temporaire qui surcadence ton cortex. Hack devastateur pendant 10 minutes. Crame des neurones.',
                'slot' => 'cyberdeck', 'consumable_type' => 'drug',
                'cooldown_seconds' => 3600,
                'effect_hack' => 10,
                'effect_duration_seconds' => 600,
                'addiction_threshold_increase' => 15,
                'overdose_chance_pct' => 8,
                'overdose_hospital_min' => 15, 'overdose_hospital_max' => 35,
                'vendor_id' => $vendorId['friperie'] ?? null, 'price' => 350,
            ],
        ];

        // Normalise toutes les rows pour qu'elles aient les memes cles (insertBatch CI4 l'exige).
        $defaults = [
            'description' => null, 'image_path' => null, 'model_path' => null,
            'vendor_id' => null, 'price' => 0,
            'bonus_force' => 0, 'bonus_blindage' => 0, 'bonus_reflexes' => 0, 'bonus_hack' => 0,
            'starter' => 0, 'discontinued' => 0, 'discontinued_at' => null,
            'consumable_type' => null, 'cooldown_seconds' => 0,
            'effect_hp' => 0, 'effect_nrg' => 0, 'effect_nrv' => 0,
            'effect_force' => 0, 'effect_blindage' => 0, 'effect_reflexes' => 0, 'effect_hack' => 0,
            'effect_hp_max' => 0, 'effect_nrg_max' => 0, 'effect_nrv_max' => 0,
            'effect_duration_seconds' => 0,
            'addiction_threshold_increase' => 0, 'overdose_chance_pct' => 0,
            'overdose_hospital_min' => 0, 'overdose_hospital_max' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ];
        foreach ($consumables as &$c) {
            $c = array_merge($defaults, $c);
        }
        unset($c);

        // Idempotent : ne reinsere pas les slugs deja presents.
        $existing = $this->db->table('items')->select('slug')->whereIn('slug', array_column($consumables, 'slug'))->get()->getResultArray();
        $existingSlugs = array_column($existing, 'slug');
        $toInsert = array_values(array_filter($consumables, static fn ($c) => ! in_array($c['slug'], $existingSlugs, true)));
        if ($toInsert !== []) {
            $this->db->table('items')->insertBatch($toInsert);
        }
    }

    public function down()
    {
        // Supprime les seeds.
        $this->db->table('items')->whereIn('slug', [
            'cnsm-energy-drink', 'cnsm-medkit', 'cnsm-stim-combat', 'cnsm-neuro-spike',
        ])->delete();

        $this->forge->dropTable('player_active_effects');

        foreach (['addiction_level', 'addiction_updated_at', 'last_booster_at', 'last_drug_at'] as $col) {
            $this->forge->dropColumn('players', $col);
        }

        foreach ([
            'consumable_type', 'cooldown_seconds',
            'effect_hp', 'effect_nrg', 'effect_nrv',
            'effect_force', 'effect_blindage', 'effect_reflexes', 'effect_hack',
            'effect_hp_max', 'effect_nrg_max', 'effect_nrv_max',
            'effect_duration_seconds',
            'addiction_threshold_increase',
            'overdose_chance_pct', 'overdose_hospital_min', 'overdose_hospital_max',
        ] as $col) {
            $this->forge->dropColumn('items', $col);
        }
    }
}
