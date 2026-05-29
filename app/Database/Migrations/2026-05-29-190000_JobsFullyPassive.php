<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jobs entierement passifs : pas de bouton "Travailler". Chaque jour a l'heure de paie,
 * le tick verse automatiquement au joueur employe :
 *   - daily_salary (deja en place)
 *   - job_daily_xp_gain points d'XP de job (configurable)
 *   - job_daily_stat_gain points dans chacune des 2 stats du job (configurable)
 *
 * Cette migration ajoute juste les 2 game_settings, le tick fera le reste.
 */
class JobsFullyPassive extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            [
                'setting_key' => 'job_daily_xp_gain',
                'value'       => '10',
                'label'       => 'Jobs : XP par jour de travail',
                'description' => 'Points d\'XP de job ajoutes a chaque paye quotidienne.',
                'type'        => 'int',
                'category'    => 'jobs',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'setting_key' => 'job_daily_stat_gain',
                'value'       => '1',
                'label'       => 'Jobs : gain de stat par jour',
                'description' => 'Points ajoutes a chaque stat job boostee (les 2 stats du job) chaque jour.',
                'type'        => 'int',
                'category'    => 'jobs',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'job_daily_xp_gain', 'job_daily_stat_gain',
        ])->delete();
    }
}
