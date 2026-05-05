<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMediaAndDiscontinuedToItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('items', [
            // "Hors-circuit" : item retiré du catalogue actif. Toujours en BDD pour les inventaires
            // déjà existants, mais ne peut plus être ré-équipé. Affiché côté joueur dans "Cache obsolète".
            'discontinued' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'starter',
            ],
            'discontinued_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'discontinued',
            ],
            // Image PNG/JPG (optionnel). Path relatif depuis writable/uploads/items/.
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'discontinued_at',
            ],
            // Modèle 3D .glb/.gltf (optionnel). Si présent, prend la priorité sur l'image dans le viewer.
            'model_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'image_path',
            ],
        ]);
        $this->db->query('CREATE INDEX idx_items_discontinued ON items(discontinued)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_items_discontinued ON items');
        $this->forge->dropColumn('items', ['discontinued', 'discontinued_at', 'image_path', 'model_path']);
    }
}
