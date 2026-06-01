<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajoute le setting vendor_buyback_pct : pourcentage du prix d'achat que le vendor PNJ
 * paie quand le joueur lui revend un item. Style Torn city-sell.
 */
class AddVendorBuyback extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insert([
            'setting_key' => 'vendor_buyback_pct',
            'value'       => '50',
            'label'       => 'Vendor : pourcentage de rachat (%)',
            'type'        => 'int',
            'category'    => 'bazaar',
            'description' => 'Pourcentage du prix de base que le vendor PNJ paie au joueur pour racheter un item.',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->where('setting_key', 'vendor_buyback_pct')->delete();
    }
}
