<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bazaar joueur-a-joueur, style Torn : chaque joueur peut lister N items a un prix unitaire
 * sur sa fiche profil. Les acheteurs paient 100%, le vendeur recoit 100% - fee%. La fee est
 * un sink (detruite, anti-inflation).
 *
 * Un listing represente N exemplaires d'un meme item au meme prix. Quand on liste, on
 * decremente la quantite cote player_items ; quand on vend, on decremente la quantite cote
 * bazaar_listings (et on supprime si zero).
 */
class CreateBazaarTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'seller_player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'item_id'           => ['type' => 'BIGINT', 'unsigned' => true],
            'quantity'          => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'unit_price'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('seller_player_id');
        $this->forge->addKey('item_id');
        $this->forge->addForeignKey('seller_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id',          'items',   'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bazaar_listings');

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            [
                'setting_key' => 'bazaar_fee_pct', 'value' => '5',
                'label' => 'Bazaar : fee vendeur (%)', 'type' => 'int', 'category' => 'bazaar',
                'description' => 'Pourcentage debite du prix de vente (sink, n\'est verse a personne). Le vendeur recoit 100% - fee%.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'bazaar_max_listings_per_player', 'value' => '50',
                'label' => 'Bazaar : max listings actifs par joueur', 'type' => 'int', 'category' => 'bazaar',
                'description' => 'Limite anti-spam : nombre max de listings actifs simultanes par joueur.',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'bazaar_fee_pct', 'bazaar_max_listings_per_player',
        ])->delete();
        $this->forge->dropTable('bazaar_listings');
    }
}
