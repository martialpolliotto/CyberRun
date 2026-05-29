<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Le combat coute desormais une grosse dose de nerve a l'engagement (default 25)
 * et plus rien par tour. Plus realiste : tu engages, tu vas au bout.
 *
 * - Renomme combat_nerve_per_turn -> combat_nerve_to_start, value 25.
 */
class RetuneCombatNerveCost extends Migration
{
    public function up()
    {
        $this->db->table('game_settings')->where('setting_key', 'combat_nerve_per_turn')->update([
            'setting_key' => 'combat_nerve_to_start',
            'value'       => '25',
            'label'       => 'Combat : nerve à l\'engagement',
            'description' => 'Nerve consommée au moment où tu déclenches un combat. Les actions au sein du combat ne coûtent rien.',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->where('setting_key', 'combat_nerve_to_start')->update([
            'setting_key' => 'combat_nerve_per_turn',
            'value'       => '1',
            'label'       => 'Combat : nerve par tour',
            'description' => 'Nerve consommee a chaque action en combat.',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
