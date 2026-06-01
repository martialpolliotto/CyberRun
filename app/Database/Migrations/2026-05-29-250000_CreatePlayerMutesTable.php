<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mute chat separe de player_relations.
 *
 * Avant : Chat::mutedPlayerIds overloadait relation_type='enemy' pour cacher les
 * messages dans le widget. Probleme : ajouter une 'enemy relation' (rivalite combat,
 * faction enemies, leaderboards rivaux a venir) embarquait silencieusement le mute,
 * et inversement.
 *
 * Maintenant : 1 table dediee, 1 semantique. Les deux concepts evoluent independamment.
 */
class CreatePlayerMutesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'muted_player_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'muted_player_id']);
        $this->forge->addKey('player_id');
        $this->forge->addForeignKey('player_id',       'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('muted_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_mutes');
    }

    public function down()
    {
        $this->forge->dropTable('player_mutes');
    }
}
