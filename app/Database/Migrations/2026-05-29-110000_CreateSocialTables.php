<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Trois tables pour les interactions sociales/PvP autour du profil joueur :
 *
 * - player_combat_stats : compteurs combat agreges (1 row par player, cree a la volee).
 *   Les colonnes sont a 0 tant que le systeme de combat n'existe pas, mais l'UI les
 *   affiche deja pour preparer la phase suivante.
 *
 * - player_relations : qui considere qui comme ami/ennemi/cible. Mono-direction
 *   (A->B n'implique pas B->A). UNIQUE (player_id, target_player_id, relation_type)
 *   pour empecher les doublons.
 *
 * - bounties : primes placees sur la tete d'un joueur. Quand le combat existera,
 *   le tueur empochera le pot. Une bounty reste 'active' jusqu'a etre 'claimed' ou
 *   'cancelled' (par le placeur). Plusieurs bounties peuvent etre actives sur une
 *   meme cible (chacune avec son placeur et son montant).
 */
class CreateSocialTables extends Migration
{
    public function up()
    {
        // ---- player_combat_stats ----
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'attacks_won'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'attacks_lost'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'defenses_won'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'defenses_lost'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'kills'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'deaths'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'kill_streak'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'best_kill_streak'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('player_id');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_combat_stats');

        // ---- player_relations ----
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'target_player_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'relation_type'    => ['type' => 'VARCHAR', 'constraint' => 16],  // 'friend' | 'enemy' | 'target'
            'note'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'target_player_id', 'relation_type']);
        $this->forge->addKey(['player_id', 'relation_type']);
        $this->forge->addForeignKey('player_id',        'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_relations');

        // ---- bounties ----
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'placer_player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'target_player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'amount'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'message'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'active'],  // active | claimed | cancelled
            'claimed_by_player_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'claimed_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['target_player_id', 'status']);
        $this->forge->addKey(['placer_player_id', 'status']);
        $this->forge->addForeignKey('placer_player_id',     'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_player_id',     'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('claimed_by_player_id', 'players', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('bounties');
    }

    public function down()
    {
        $this->forge->dropTable('bounties');
        $this->forge->dropTable('player_relations');
        $this->forge->dropTable('player_combat_stats');
    }
}
