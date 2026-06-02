<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Daily missions (retention) : pool de templates + assignments par joueur/jour.
 *
 * Rotation GLOBALE : meme 3 missions pour tout le monde par jour, picks deterministes
 * par date. Donne un sentiment de communaute (tout le monde fait X aujourd'hui) et
 * simplifie le caching/social ("As-tu fait les dailies?").
 *
 * - daily_mission_templates : pool de templates (objective_type + target + count + rewards)
 * - daily_assignments : 1 ligne par player + day_date + template, track progress + claim
 */
class CreateDailyMissions extends Migration
{
    public function up()
    {
        // ---- Templates pool ----
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 120],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'objective_type'   => ['type' => 'VARCHAR', 'constraint' => 32],
            'objective_target' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => '*'],
            'objective_count'  => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'reward_credits'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_xp'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('active');
        $this->forge->createTable('daily_mission_templates');

        // ---- Assignments par joueur / jour ----
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'template_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'day_date'     => ['type' => 'DATE'],
            'progress'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'claimed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'day_date', 'template_id']);
        $this->forge->addKey(['player_id', 'day_date']);
        $this->forge->addForeignKey('player_id',   'players',                   'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('template_id', 'daily_mission_templates',   'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('daily_assignments');

        // ---- Seed pool 8 templates ----
        $now = date('Y-m-d H:i:s');
        $templates = [
            ['daily-crime-5',          'Casseur du jour',     '5 crimes commis (peu importe la categorie ou l\'issue).',                'commit_crime',  '*',          5, 250, 50],
            ['daily-train-3',          'Au Lab',               '3 entrainements au Lab (peu importe la stat).',                          'train_stat',    '*',          3, 200, 40],
            ['daily-bazaar',           'Direction marche',     'Passe sur le bazaar.',                                                   'visit_page',    'bazaar',     1, 100, 20],
            ['daily-buy',              'Shopping',             'Achete 1 item chez un marchand PNJ.',                                    'buy_item',      '*',          1, 150, 30],
            ['daily-equip',            'Tu te bichonnes',      'Equipe-toi (1 item).',                                                   'equip_slot',    '*',          1, 100, 25],
            ['daily-crime-hack-3',     'Trio numerique',       '3 crimes de la categorie Hack.',                                         'commit_crime',  'hack',       3, 300, 60],
            ['daily-crime-pickpocket-3','Trio pickpocket',     '3 crimes de la categorie Pickpocket.',                                   'commit_crime',  'pickpocket', 3, 300, 60],
            ['daily-fixers',           'Tu causes',            'Visite tes fixers.',                                                     'visit_page',    'fixers',     1, 100, 20],
        ];

        $rows = [];
        foreach ($templates as $t) {
            $rows[] = [
                'slug' => $t[0], 'name' => $t[1], 'description' => $t[2],
                'objective_type' => $t[3], 'objective_target' => $t[4], 'objective_count' => $t[5],
                'reward_credits' => $t[6], 'reward_xp' => $t[7],
                'active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        $this->db->table('daily_mission_templates')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('daily_assignments');
        $this->forge->dropTable('daily_mission_templates');
    }
}
