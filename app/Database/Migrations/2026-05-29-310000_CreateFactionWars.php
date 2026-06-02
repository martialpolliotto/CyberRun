<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Faction wars MVP.
 *
 * Flow :
 *  1. Leader A declare la guerre a B : stake_credits debites de la treasury de A,
 *     row status='pending' creee.
 *  2. Leader B accepte (stake debites) -> status='active', ends_at = now + duration.
 *     Ou refuse -> status='cancelled', refund A.
 *  3. Pendant active : hospitalisations infligees entre membres incrementent score_a/b.
 *  4. Fin : score_cap atteint OU ends_at expire. Vainqueur = score le + haut, draw possible.
 *     Pot = stake_a + stake_b reverse au vainqueur (draw : split 50/50).
 *
 * 1 seule guerre active par faction a la fois (UNIQUE partiel impossible en MySQL,
 * on enforce au niveau du code via activeForFaction()).
 */
class CreateFactionWars extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'faction_a_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'faction_b_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'],
            // pending | active | ended_a_won | ended_b_won | ended_draw | cancelled
            'stake_a'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'stake_b'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'score_a'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'score_b'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'declared_at'    => ['type' => 'DATETIME', 'null' => true],
            'accepted_at'    => ['type' => 'DATETIME', 'null' => true],
            'ends_at'        => ['type' => 'DATETIME', 'null' => true],
            'ended_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey(['faction_a_id', 'status']);
        $this->forge->addKey(['faction_b_id', 'status']);
        $this->forge->addForeignKey('faction_a_id', 'factions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('faction_b_id', 'factions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('faction_wars');

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            [
                'setting_key' => 'war_duration_hours', 'value' => '168',
                'label' => 'Guerre faction : duree max (heures)',
                'type' => 'int', 'category' => 'faction',
                'description' => 'Duree max d\'une guerre. Default 168h = 7 jours.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'war_score_cap', 'value' => '100',
                'label' => 'Guerre faction : score cap (fin anticipee)',
                'type' => 'int', 'category' => 'faction',
                'description' => 'Si un cote atteint ce nombre d\'hospitalisations, la guerre se termine immediatement.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'war_stake_credits', 'value' => '100000',
                'label' => 'Guerre faction : mise par cote (credits)',
                'type' => 'int', 'category' => 'faction',
                'description' => 'Credits stakes par chaque faction au moment de declarer/accepter. Pot total = 2 * stake, reverse au vainqueur.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'war_respect_multiplier', 'value' => '2',
                'label' => 'Guerre faction : multiplicateur de respect',
                'type' => 'int', 'category' => 'faction',
                'description' => 'Pendant une guerre active, les rewards respect (crime / hospitalize) des membres concernes sont multiplies par N.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'war_pending_expire_hours', 'value' => '24',
                'label' => 'Guerre faction : expiration des declarations en attente (heures)',
                'type' => 'int', 'category' => 'faction',
                'description' => 'Une declaration non acceptee par la cible apres N heures est auto-annulee (refund declarant).',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'war_duration_hours', 'war_score_cap', 'war_stake_credits',
            'war_respect_multiplier', 'war_pending_expire_hours',
        ])->delete();
        $this->forge->dropTable('faction_wars');
    }
}
