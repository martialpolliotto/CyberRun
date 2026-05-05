<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'           => ['type' => 'INT', 'unsigned' => true],
            'level'             => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'xp'                => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'credits'           => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 100],
            'hp_current'        => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'hp_max'            => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'energy_current'    => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'energy_max'        => ['type' => 'INT', 'unsigned' => true, 'default' => 150],
            'nerve_current'     => ['type' => 'INT', 'unsigned' => true, 'default' => 25],
            'nerve_max'         => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'stat_force'        => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'stat_blindage'     => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'stat_reflexes'     => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'stat_hack'         => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'in_hospital_until' => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('level');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');

        $this->forge->createTable('players');
    }

    public function down()
    {
        $this->forge->dropTable('players');
    }
}
