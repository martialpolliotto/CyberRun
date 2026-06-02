<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Online status : nouvelle colonne players.last_seen_at, mise a jour a chaque
 * page chargee (via BaseController::initController, skipped pour les bots).
 *
 * 'Online' = derive a la lecture : last_seen_at > NOW() - threshold_seconds.
 * Default threshold via game_settings.online_threshold_seconds = 300 (5 min).
 */
class AddLastSeenAt extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'last_seen_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'last_login_at',
            ],
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insert([
            'setting_key' => 'online_threshold_seconds',
            'value'       => '300',
            'label'       => 'Online : seuil de derniere activite (s)',
            'type'        => 'int',
            'category'    => 'retention',
            'description' => 'Un joueur est considere online si last_seen_at > NOW() - N secondes. Default 300 = 5 min.',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->where('setting_key', 'online_threshold_seconds')->delete();
        $this->forge->dropColumn('players', 'last_seen_at');
    }
}
