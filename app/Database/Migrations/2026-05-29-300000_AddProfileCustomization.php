<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Personnalisation profil joueur :
 *  - bio          : description longue (max 2000), affichee dans une card sur /u/{name}
 *  - signature    : tagline courte (max 200), affichee a cote du pseudo
 *  - avatar_path  : URL publique de l'image avatar (uploads/avatars/{userId}.ext)
 *
 * Tous les 3 sont optionnels (NULL par defaut, l'UI tombe sur des fallbacks).
 */
class AddProfileCustomization extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'bio' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'is_donator',
            ],
            'signature' => [
                'type'      => 'VARCHAR',
                'constraint'=> 200,
                'null'      => true,
                'after'     => 'bio',
            ],
            'avatar_path' => [
                'type'      => 'VARCHAR',
                'constraint'=> 255,
                'null'      => true,
                'after'     => 'signature',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('players', ['bio', 'signature', 'avatar_path']);
    }
}
