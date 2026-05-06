<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVendorAndPriceToItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('items', [
            'vendor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'slot',
            ],
            'price' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'default'  => 0,
                'after'    => 'vendor_id',
            ],
        ]);
        $this->forge->addForeignKey('vendor_id', 'vendors', 'id', '', 'SET NULL', 'items');
        $this->db->query('CREATE INDEX idx_items_vendor ON items(vendor_id)');
        $this->db->query('CREATE INDEX idx_items_price ON items(price)');

        // Récupère les ids des 3 vendors seedés.
        $vendors = [];
        foreach ($this->db->table('vendors')->get()->getResult() as $v) {
            $vendors[$v->slug] = (int) $v->id;
        }

        // Mapping slot -> vendor pour les items existants.
        $slotToVendor = [
            'optique'     => $vendors['ripperdoc'] ?? null,
            'cyberdeck'   => $vendors['ripperdoc'] ?? null,
            'arme1'       => $vendors['armurerie'] ?? null,
            'arme2'       => $vendors['armurerie'] ?? null,
            'combinaison' => $vendors['friperie']  ?? null,
            'bottes'      => $vendors['friperie']  ?? null,
        ];
        foreach ($slotToVendor as $slot => $vid) {
            if ($vid !== null) {
                $this->db->table('items')
                    ->where('slot', $slot)
                    ->update(['vendor_id' => $vid]);
            }
        }

        // Pricer les 6 starters à 50 crédits (filet de sécurité, achat possible si perdu).
        $this->db->table('items')->where('starter', 1)->update(['price' => 50]);

        // Seed des 7 nouveaux items achetables (pas starter).
        $now  = date('Y-m-d H:i:s');
        $base = [
            'description'    => '',
            'bonus_force'    => 0,
            'bonus_blindage' => 0,
            'bonus_reflexes' => 0,
            'bonus_hack'     => 0,
            'starter'        => 0,
            'discontinued'   => 0,
            'discontinued_at'=> null,
            'image_path'     => null,
            'model_path'     => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        $this->db->table('items')->insertBatch([
            // Armurerie
            array_merge($base, [
                'slug' => 'fusil-assaut', 'name' => 'Fusil d\'assaut', 'slot' => 'arme1',
                'vendor_id' => $vendors['armurerie'], 'price' => 800,
                'description' => 'Fusil militaire reconverti pour le marché civil. Précis, fiable, bruyant.',
                'bonus_force' => 6,
            ]),
            array_merge($base, [
                'slug' => 'pistolet-plasma', 'name' => 'Pistolet à plasma', 'slot' => 'arme1',
                'vendor_id' => $vendors['armurerie'], 'price' => 1500,
                'description' => 'Tir énergie ionisée. Surchauffe vite, mais fait des dégâts indécents.',
                'bonus_force' => 9,
            ]),
            array_merge($base, [
                'slug' => 'sabre-laser', 'name' => 'Sabre laser', 'slot' => 'arme2',
                'vendor_id' => $vendors['armurerie'], 'price' => 700,
                'description' => 'Lame plasma rétractable. Coupe le métal, et tout le reste.',
                'bonus_force' => 4, 'bonus_reflexes' => 2,
            ]),
            // Ripperdoc
            array_merge($base, [
                'slug' => 'optique-mk2', 'name' => 'Optique Mk.II', 'slot' => 'optique',
                'vendor_id' => $vendors['ripperdoc'], 'price' => 600,
                'description' => 'Cybereyes améliorés : zoom optique, mode infrarouge, overlay tactique léger.',
                'bonus_reflexes' => 4, 'bonus_hack' => 2,
            ]),
            array_merge($base, [
                'slug' => 'cyberdeck-mk2', 'name' => 'Cyberdeck Mk.II', 'slot' => 'cyberdeck',
                'vendor_id' => $vendors['ripperdoc'], 'price' => 1200,
                'description' => 'Deck mid-range avec processeurs neuraux dédiés. Pour le netrunner sérieux.',
                'bonus_hack' => 6,
            ]),
            // Friperie
            array_merge($base, [
                'slug' => 'combi-combat', 'name' => 'Combi de combat', 'slot' => 'combinaison',
                'vendor_id' => $vendors['friperie'], 'price' => 800,
                'description' => 'Combinaison kevlar avec inserts céramiques. Encombrante, mais efficace contre les balles.',
                'bonus_blindage' => 6,
            ]),
            array_merge($base, [
                'slug' => 'bottes-rapides', 'name' => 'Bottes Rapides', 'slot' => 'bottes',
                'vendor_id' => $vendors['friperie'], 'price' => 600,
                'description' => 'Bottes à amortisseurs cyber, propulsion légère assistée. Tu cours sans te fatiguer.',
                'bonus_reflexes' => 4,
            ]),
        ]);
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_items_price ON items');
        $this->db->query('DROP INDEX idx_items_vendor ON items');
        $this->forge->dropForeignKey('items', 'items_vendor_id_foreign');
        $this->forge->dropColumn('items', ['vendor_id', 'price']);
        // Ne supprime PAS les items insérés (down devrait les retirer mais on garde pour safety).
    }
}
