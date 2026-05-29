<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Factions MVP : crew/gang persistant.
 *
 * - factions          : 1 ligne par crew (nom, tag court, leader, tresorerie, respect cumule).
 * - faction_members   : appartenance + rank (leader|member) + contribution agregee.
 *                       Le joueur a deja un faction_id sur players ; cette table double-emploi
 *                       est volontaire : elle stocke join_date + rank + contrib, ce que la
 *                       colonne players.faction_id ne couvre pas.
 * - faction_applications : candidatures avec status pending|accepted|rejected|cancelled.
 *
 * Game settings seeds : cout creation, niveau min, respect gagne par crime success.
 */
class CreateFactionsTables extends Migration
{
    public function up()
    {
        // ---- factions ----
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 80],
            'tag'               => ['type' => 'VARCHAR', 'constraint' => 8],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'leader_player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'treasury'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'respect'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'members_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->addUniqueKey('tag');
        $this->forge->addKey('respect');
        $this->forge->addForeignKey('leader_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('factions');

        // ---- faction_members ----
        $this->forge->addField([
            'id'                   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'faction_id'           => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'rank'                 => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'member'], // leader | member
            'contributed_credits'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'contributed_respect'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'joined_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('player_id'); // 1 joueur = 1 faction max
        $this->forge->addKey(['faction_id', 'rank']);
        $this->forge->addForeignKey('faction_id', 'factions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id',  'players',  'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('faction_members');

        // ---- faction_applications ----
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'faction_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'message'               => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'                => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'], // pending|accepted|rejected|cancelled
            'decided_by_player_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'decided_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['faction_id', 'status']);
        $this->forge->addKey(['player_id', 'status']);
        $this->forge->addForeignKey('faction_id',           'factions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id',            'players',  'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('decided_by_player_id', 'players',  'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('faction_applications');

        // ---- Game settings ----
        $now = date('Y-m-d H:i:s');
        $settings = [
            [
                'setting_key' => 'faction_create_cost', 'value' => '100000',
                'label' => 'Faction : coût de création (crédits)', 'type' => 'int', 'category' => 'faction',
                'description' => 'Crédits débités au fondateur lors de la création d\'une faction.',
            ],
            [
                'setting_key' => 'faction_create_min_level', 'value' => '5',
                'label' => 'Faction : niveau minimum pour créer', 'type' => 'int', 'category' => 'faction',
                'description' => 'Empêche les nouveaux comptes de fonder une faction immédiatement.',
            ],
            [
                'setting_key' => 'faction_respect_per_crime', 'value' => '1',
                'label' => 'Faction : respect gagné par crime membre', 'type' => 'int', 'category' => 'faction',
                'description' => 'Respect ajouté à la faction quand un membre réussit un crime.',
            ],
            [
                'setting_key' => 'faction_respect_per_hospitalize', 'value' => '5',
                'label' => 'Faction : respect gagné par hospitalisation infligée', 'type' => 'int', 'category' => 'faction',
                'description' => 'Respect ajouté à la faction quand un membre hospitalise un autre joueur.',
            ],
            [
                'setting_key' => 'faction_max_members', 'value' => '50',
                'label' => 'Faction : nombre max de membres', 'type' => 'int', 'category' => 'faction',
                'description' => 'Au-dela, les candidatures sont bloquees automatiquement.',
            ],
        ];
        foreach ($settings as &$s) { $s['created_at'] = $now; $s['updated_at'] = $now; }
        unset($s);
        $this->db->table('game_settings')->insertBatch($settings);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'faction_create_cost', 'faction_create_min_level',
            'faction_respect_per_crime', 'faction_respect_per_hospitalize',
            'faction_max_members',
        ])->delete();
        $this->forge->dropTable('faction_applications');
        $this->forge->dropTable('faction_members');
        $this->forge->dropTable('factions');
    }
}
