<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Login streak (retention quotidienne).
 *
 * - players.login_streak_days : nombre de jours consecutifs de connexion
 * - players.last_login_at      : DATE (YYYY-MM-DD) du dernier login compte
 * - players.last_streak_reward : credits gagnes a la derniere connexion (pour affichage)
 *
 * Reward escalating : base + increment * streak, cape par streak_reward_max.
 */
class AddLoginStreak extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'login_streak_days' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'default' => 0,
                'after'   => 'addiction_level',
            ],
            'last_login_at' => [
                'type'    => 'DATE',
                'null'    => true,
                'after'   => 'login_streak_days',
            ],
            'last_streak_reward' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'default' => 0,
                'after'   => 'last_login_at',
            ],
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            [
                'setting_key' => 'streak_reward_base',
                'value'       => '50',
                'label'       => 'Streak : reward de base (jour 1)',
                'type'        => 'int',
                'category'    => 'retention',
                'description' => 'Credits accordes la 1ere connexion (jour 1 du streak).',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'streak_reward_increment',
                'value'       => '25',
                'label'       => 'Streak : increment par jour supplementaire',
                'type'        => 'int',
                'category'    => 'retention',
                'description' => 'Reward = base + (streak - 1) * increment, cape par streak_reward_max.',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'streak_reward_max',
                'value'       => '500',
                'label'       => 'Streak : reward max (cap)',
                'type'        => 'int',
                'category'    => 'retention',
                'description' => 'Plafond du reward streak. Default 500c (atteint au jour 19).',
                'created_at'  => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'streak_reward_base', 'streak_reward_increment', 'streak_reward_max',
        ])->delete();
        $this->forge->dropColumn('players', ['login_streak_days', 'last_login_at', 'last_streak_reward']);
    }
}
