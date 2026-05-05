<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'slot'            => ['type' => 'VARCHAR', 'constraint' => 30],
            'bonus_force'     => ['type' => 'INT', 'default' => 0],
            'bonus_blindage'  => ['type' => 'INT', 'default' => 0],
            'bonus_reflexes'  => ['type' => 'INT', 'default' => 0],
            'bonus_hack'      => ['type' => 'INT', 'default' => 0],
            'starter'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('slot');
        $this->forge->addKey('starter');
        $this->forge->createTable('items');

        // Seed des 6 items de départ (un par slot). Marqués starter=1 pour que
        // le PlayerItemModel puisse les attribuer automatiquement à l'inscription.
        // NB: insertBatch exige des clés uniformes -> on liste TOUS les bonus_* à 0 par défaut.
        $now  = date('Y-m-d H:i:s');
        $base = [
            'description'    => '',
            'bonus_force'    => 0,
            'bonus_blindage' => 0,
            'bonus_reflexes' => 0,
            'bonus_hack'     => 0,
            'starter'        => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        $this->db->table('items')->insertBatch([
            array_merge($base, [
                'slug' => 'optique-mk1', 'name' => 'Optique Mk.I', 'slot' => 'optique',
                'description' => 'Cybereyes d\'entrée de gamme. Vision augmentée basique.',
                'bonus_reflexes' => 2, 'bonus_hack' => 1,
            ]),
            array_merge($base, [
                'slug' => 'trench-renforce', 'name' => 'Trench Renforcé', 'slot' => 'combinaison',
                'description' => 'Manteau long avec inserts de chrome au niveau du torse.',
                'bonus_blindage' => 3,
            ]),
            array_merge($base, [
                'slug' => 'synth-bottes', 'name' => 'Synth-Bottes', 'slot' => 'bottes',
                'description' => 'Bottes synthétiques amorties pour la course urbaine.',
                'bonus_reflexes' => 2,
            ]),
            array_merge($base, [
                'slug' => 'pistolet-9mm', 'name' => 'Pistolet 9mm', 'slot' => 'arme1',
                'description' => 'Arme de poing standard. Fiable, sans surprise.',
                'bonus_force' => 3,
            ]),
            array_merge($base, [
                'slug' => 'lame-mono-fil', 'name' => 'Lame mono-fil', 'slot' => 'arme2',
                'description' => 'Couteau à lame moléculaire, tranche presque tout.',
                'bonus_force' => 1, 'bonus_reflexes' => 1,
            ]),
            array_merge($base, [
                'slug' => 'cyberdeck-mk1', 'name' => 'Cyberdeck Mk.I', 'slot' => 'cyberdeck',
                'description' => 'Deck d\'occasion. Suffisant pour les premières intrusions.',
                'bonus_hack' => 3,
            ]),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('items');
    }
}
