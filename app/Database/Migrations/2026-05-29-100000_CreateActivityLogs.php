<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Log d'activite par joueur, facon Torn. Chaque ligne = un evenement
 * (un crime tente, un train, un bust subi, une mission claim, etc.).
 *
 * - action_key : clef de traduction (Log.<slug>), permet le passage EN sans reecrire en base
 * - params    : JSON des variables injectees dans la phrase ({credits}, {item_name}, etc.)
 * - target_player_id : auteur si tu es la cible / cible si tu es l'auteur
 * - related_id : id contextuel (mission_id, crime_id, item_id...) pour faire des liens cliquables
 *
 * Indexe sur (player_id, created_at DESC) pour pagination rapide.
 */
class CreateActivityLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'category'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'action_key'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'params'           => ['type' => 'JSON', 'null' => true],
            'target_player_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'related_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['player_id', 'created_at']);
        $this->forge->addKey(['player_id', 'category']);
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_player_id', 'players', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('activity_logs');
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs');
    }
}
