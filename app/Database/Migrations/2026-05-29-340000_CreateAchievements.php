<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Achievements / trophees : definitions + tracking par joueur.
 *
 * Compteur incremental : pour chaque event matchant (trigger_type + trigger_target),
 * player_achievements.progress++. Quand progress >= count, unlocked_at est pose et
 * la reward credit/xp distribuee.
 *
 * Hidden : si 1, le trophe n'apparait pas dans la liste tant qu'il n'est pas unlock.
 */
class CreateAchievements extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 120],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'icon'            => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'bi-award'],
            'category'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'general'],
            'trigger_type'    => ['type' => 'VARCHAR', 'constraint' => 32],
            'trigger_target'  => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => '*'],
            'trigger_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'reward_credits'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_xp'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'hidden'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['trigger_type', 'trigger_target']);
        $this->forge->createTable('achievements');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'achievement_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'progress'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'unlocked_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'achievement_id']);
        $this->forge->addKey(['player_id', 'unlocked_at']);
        $this->forge->addForeignKey('player_id',      'players',     'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('achievement_id', 'achievements','id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_achievements');

        // Seed initial : 18 achievements repartis sur 6 categories.
        // Format : [slug, name, description, icon, category, trigger_type, trigger_target, count, credits, xp]
        $now = date('Y-m-d H:i:s');
        $items = [
            // Crime
            ['ach-crime-10',   'Petit délinquant',        '10 crimes commis.',                                    'bi-mask',         'crime',  'commit_crime', '*',          10,    500,   50,  0,  1],
            ['ach-crime-100',  'Pro du braquage',         '100 crimes commis.',                                   'bi-mask-fill',    'crime',  'commit_crime', '*',         100,   5000,  500, 0,  2],
            ['ach-crime-1000', 'Maître du crime',         '1000 crimes commis. Vous etes une légende.',           'bi-fire',         'crime',  'commit_crime', '*',        1000,  50000, 5000, 0,  3],
            ['ach-hack-50',   'Hacker specialiste',       '50 crimes Hack reussis.',                              'bi-cpu',          'crime',  'commit_crime', 'hack',       50,   2000,  200, 0,  4],
            ['ach-pp-50',     'Pickpocket virtuose',      '50 crimes Pickpocket reussis.',                        'bi-hand-index',   'crime',  'commit_crime', 'pickpocket', 50,   2000,  200, 0,  5],
            // Train
            ['ach-train-50',  'Rat de Lab',               '50 sessions d\'entrainement.',                         'bi-flask',        'train',  'train_stat',   '*',          50,   1000,  100, 0, 10],
            ['ach-train-500', 'Iron Body',                '500 sessions d\'entrainement.',                        'bi-stars',        'train',  'train_stat',   '*',         500,  10000, 1000, 0, 11],
            // Combat
            ['ach-hosp-1',    'Premier sang',             'Hospitaliser ton premier adversaire.',                 'bi-droplet-fill', 'combat', 'combat_hospitalize', '*',     1,   1000,  100, 0, 20],
            ['ach-hosp-10',   'Brawler',                  '10 adversaires hospitalises.',                         'bi-shield-fill',  'combat', 'combat_hospitalize', '*',    10,   5000,  500, 0, 21],
            ['ach-hosp-100',  'Boucher de Night City',    '100 adversaires hospitalises.',                        'bi-virus',        'combat', 'combat_hospitalize', '*',   100,  25000, 2500, 0, 22],
            ['ach-bounty-5',  'Chasseur de primes',       'Claim 5 bounties.',                                    'bi-crosshair',    'combat', 'bounty_claimed', '*',         5,   5000,  500, 0, 23],
            // Level
            ['ach-lvl-5',     'Plus un newbie',           'Atteindre le niveau 5.',                               'bi-arrow-up-circle','level','level_up', '*',                4,   1000,  100, 0, 30],
            ['ach-lvl-25',    'Aguerri',                  'Atteindre le niveau 25.',                              'bi-arrow-up-square','level','level_up', '*',               24,  10000, 1000, 0, 31],
            ['ach-lvl-50',    'Veteran',                  'Atteindre le niveau 50.',                              'bi-trophy',       'level',  'level_up', '*',               49,  50000, 5000, 0, 32],
            // Social
            ['ach-spy-25',    'Maître espion',            '25 espionnages effectues.',                            'bi-eye',          'social', 'spy_done', '*',               25,   5000,  500, 0, 40],
            ['ach-friend-5',  'Reseauteur',               'Ajouter 5 joueurs en amis.',                           'bi-person-heart', 'social', 'relation_friend_added', '*',   5,    500,   50, 0, 41],
            ['ach-msg-100',   'Bavard',                   'Envoyer 100 messages prives.',                         'bi-envelope-fill','social', 'message_sent',   '*',        100,    500,   50, 0, 42],
            // Eco
            ['ach-transfer-1','Generosite',               'Envoyer ton premier transfert de credits.',            'bi-cash-coin',    'eco',    'transfer_sent', '*',           1,    250,   25, 0, 50],
        ];

        $rows = [];
        foreach ($items as $i) {
            $rows[] = [
                'slug' => $i[0], 'name' => $i[1], 'description' => $i[2],
                'icon' => $i[3], 'category' => $i[4],
                'trigger_type' => $i[5], 'trigger_target' => $i[6], 'trigger_count' => $i[7],
                'reward_credits' => $i[8], 'reward_xp' => $i[9],
                'hidden' => $i[10], 'sort_order' => $i[11],
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        $this->db->table('achievements')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('player_achievements');
        $this->forge->dropTable('achievements');
    }
}
