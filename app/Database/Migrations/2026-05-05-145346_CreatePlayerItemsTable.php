<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'item_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'equipped'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'quantity'   => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['player_id', 'equipped']);
        $this->forge->addForeignKey('player_id', 'players', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'items', 'id', '', 'CASCADE');
        $this->forge->createTable('player_items');
    }

    public function down()
    {
        $this->forge->dropTable('player_items');
    }
}
