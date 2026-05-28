<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Plages horaires d'activite des bots, pour simuler des humains qui jouent surtout
 * en soiree / weekend plutot que 24/7 a fond.
 *
 * Chaque bot a une fenetre d'activite (start-end en heure serveur, peut wrap minuit)
 * et un multiplier weekend (boost de l'activite ven soir -> dim soir).
 *
 * Hors fenetre, le bot peut tout de meme agir mais a une frequence reduite par le
 * game_setting bot_off_hours_factor (defaut 10 = 10% de l'activite normale).
 *
 * Les bots existants recoivent une plage aleatoire backfill.
 */
class AddBotScheduleFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'bot_active_hour_start' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'bot_persona'],
            'bot_active_hour_end'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'bot_active_hour_start'],
            'bot_weekend_boost_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'bot_active_hour_end'],
        ]);

        // Backfill : tous les bots existants recoivent une plage aleatoire pour pas etre tous synchros.
        $bots = $this->db->table('players')->where('is_bot', 1)->select('id')->get()->getResultArray();
        foreach ($bots as $b) {
            $start  = random_int(0, 23);
            $window = random_int(6, 12);   // fenetre 6-12h
            $end    = ($start + $window) % 24;
            $boost  = random_int(20, 80);   // boost weekend +20 a +80 %
            $this->db->table('players')->where('id', (int) $b['id'])->update([
                'bot_active_hour_start' => $start,
                'bot_active_hour_end'   => $end,
                'bot_weekend_boost_pct' => $boost,
            ]);
        }

        // Game setting : facteur d'activite hors plage (en %).
        $this->db->table('game_settings')->insert([
            'setting_key' => 'bot_off_hours_factor',
            'value'       => '10',
            'label'       => 'Bots : facteur d\'activite hors plage horaire (%)',
            'description' => 'Pourcentage de la chance normale qu\'un bot agisse hors de sa fenetre active. 10 = 10x moins actif hors plage. 0 = totalement inactif.',
            'type'        => 'int',
            'category'    => 'bots',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('players', 'bot_active_hour_start');
        $this->forge->dropColumn('players', 'bot_active_hour_end');
        $this->forge->dropColumn('players', 'bot_weekend_boost_pct');
        $this->db->table('game_settings')->where('setting_key', 'bot_off_hours_factor')->delete();
    }
}
