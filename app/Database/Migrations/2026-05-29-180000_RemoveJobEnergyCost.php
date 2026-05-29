<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Travailler ne coute plus d'energie. La progression dans le job est limitee
 * uniquement par le rythme manuel des sessions (et eventuellement un cooldown
 * a ajouter plus tard si necessaire).
 */
class RemoveJobEnergyCost extends Migration
{
    public function up()
    {
        $this->db->table('jobs')->update(['work_energy_cost' => 0]);
    }

    public function down()
    {
        $values = [
            'ripperdoc' => 12, 'decker' => 10, 'corpo-guard' => 10,
            'courier'   => 8,  'net-runner' => 11, 'mercenary' => 11, 'fixer-aide' => 9,
        ];
        foreach ($values as $slug => $cost) {
            $this->db->table('jobs')->where('slug', $slug)->update(['work_energy_cost' => $cost]);
        }
    }
}
