<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Systeme de combat V2 simplifie (tour-par-tour, 3 actions : attaque/garde/fuite).
 *
 * - combats : 1 ligne par duel. Stocke les HP de combat (copie des HP joueur a l'init),
 *   l'energie consommee, le tour courant, et l'etat (ongoing/ended_attack_won/...).
 *   Les vrais HP des joueurs ne sont modifies qu'a la fin du combat (resolveEnd).
 *
 * - combat_turns : historique des tours. Chaque ligne = 1 action d'un joueur.
 *   Stocke action, hit (bool), damage, narrative pour afficher l'historique.
 *
 * Stats combat alimentees dans player_combat_stats (deja cree) a la fin.
 */
class CreateCombatTables extends Migration
{
    public function up()
    {
        // ---- combats ----
        $this->forge->addField([
            'id'                       => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'attacker_player_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'defender_player_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'ongoing'],
            // ongoing | ended_attacker_won | ended_defender_won | ended_attacker_fled | ended_defender_fled | resolved
            // 'resolved' = post-victoire traité (mug/hospitalize/leave choisi)
            'attacker_hp_remaining'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'defender_hp_remaining'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'attacker_hp_initial'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'defender_hp_initial'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'current_turn_player_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'winner_player_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'post_action'              => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],  // mug | hospitalize | leave
            'mug_amount'               => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'ended_at'                 => ['type' => 'DATETIME', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('attacker_player_id');
        $this->forge->addKey('defender_player_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('attacker_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('defender_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('combats');

        // ---- combat_turns ----
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'combat_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'turn_player_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'action'         => ['type' => 'VARCHAR', 'constraint' => 16],  // attack | guard | flee
            'hit'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'damage_dealt'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'narrative'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['combat_id', 'id']);
        $this->forge->addForeignKey('combat_id', 'combats', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('combat_turns');

        // ---- Game settings combat ----
        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            ['setting_key' => 'combat_energy_per_turn',   'value' => '5',  'label' => 'Combat : énergie par tour',         'type' => 'int', 'category' => 'combat', 'description' => 'Énergie consommée par chaque action en combat.',                                      'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_base_hit_pct',      'value' => '70', 'label' => 'Combat : chance de toucher de base','type' => 'int', 'category' => 'combat', 'description' => 'Pourcentage de base avant modificateurs de réflexes.',                              'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_min_damage',        'value' => '5',  'label' => 'Combat : dégâts minimum',            'type' => 'int', 'category' => 'combat', 'description' => 'Plancher de dégâts en cas de hit (apres reduction par blindage).',                  'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_guard_reduction_pct','value' => '50','label' => 'Combat : reduction degats en garde','type' => 'int', 'category' => 'combat', 'description' => 'Pourcentage de dégâts reduits le tour ou on a choisi "Garder".',                  'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_flee_base_pct',     'value' => '40', 'label' => 'Combat : chance de fuite de base',   'type' => 'int', 'category' => 'combat', 'description' => 'Pourcentage de base. Bonifié par les réflexes du fuyard, malus par ceux de l\'adversaire.', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_mug_pct',           'value' => '20', 'label' => 'Combat : Mug % credits voles',      'type' => 'int', 'category' => 'combat', 'description' => 'Pourcentage des crédits de la cible volés à l\'action Mug post-victoire.',           'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_hospital_min',      'value' => '10', 'label' => 'Combat : hospital min (min.)',      'type' => 'int', 'category' => 'combat', 'description' => 'Durée min de cyberclinique pour la cible si action Hospitalize choisie.',           'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_hospital_max',      'value' => '30', 'label' => 'Combat : hospital max (min.)',      'type' => 'int', 'category' => 'combat', 'description' => 'Durée max de cyberclinique.',                                                          'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_cooldown_seconds',  'value' => '300','label' => 'Combat : cooldown entre attaques (s)','type' => 'int', 'category' => 'combat', 'description' => 'Seconds entre deux attaques d\'un même joueur (5 min par défaut).',                'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'combat_xp_reward',         'value' => '20', 'label' => 'Combat : XP gagne par victoire',    'type' => 'int', 'category' => 'combat', 'description' => 'XP joueur ajoute en cas de victoire d\'un combat.',                                    'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---- last_combat_at sur players pour le cooldown ----
        $this->forge->addColumn('players', [
            'last_combat_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_drug_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('players', 'last_combat_at');
        $this->db->table('game_settings')->whereIn('setting_key', [
            'combat_energy_per_turn', 'combat_base_hit_pct', 'combat_min_damage',
            'combat_guard_reduction_pct', 'combat_flee_base_pct',
            'combat_mug_pct', 'combat_hospital_min', 'combat_hospital_max',
            'combat_cooldown_seconds', 'combat_xp_reward',
        ])->delete();
        $this->forge->dropTable('combat_turns');
        $this->forge->dropTable('combats');
    }
}
