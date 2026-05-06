<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendorsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'tagline'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'image_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'banner_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('vendors');

        // Seed des 3 marchands fixes (image_path null, à uploader via admin).
        $now = date('Y-m-d H:i:s');
        $this->db->table('vendors')->insertBatch([
            [
                'slug'        => 'armurerie',
                'name'        => 'Armurerie',
                'tagline'     => 'Du calibre pour tous les budgets.',
                'description' => 'Marchand d\'armes louche du quartier, fournit aussi bien les gangers que les corpos. Pas de questions, pas de problèmes. Tout est testé sur cible vivante avant la mise en rayon.',
                'image_path'  => null,
                'banner_path' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'ripperdoc',
                'name'        => 'Ripperdoc',
                'tagline'     => 'Le chrome, c\'est la liberté.',
                'description' => 'Ex-chirurgienne corpo virée pour pratiques non-conformes. Pose ton hardware en arrière-cour, prix corrects, anesthésiant en option. Garantie : si t\'es encore conscient en partant, c\'est que ça a marché.',
                'image_path'  => null,
                'banner_path' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'friperie',
                'name'        => 'Friperie',
                'tagline'     => 'S\'habiller, c\'est se planquer.',
                'description' => 'Vieille tailleuse qui upcycle des fringues blindées à partir de matériel récupéré. Tu rentres mal sapé, tu sors méconnaissable. Spécialité maison : doublures pare-balles cousues main.',
                'image_path'  => null,
                'banner_path' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('vendors');
    }
}
