<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Refacto banque : 4 durees fixes 7/14/21/28j avec taux progressifs, et depot
 * verrouille jusqu'a maturite (plus de retrait anticipe).
 *
 * Avant : 3 durees (1/7/30j) avec retrait anticipe = principal seul.
 * Apres : 4 durees fixes, pas de sortie possible avant matures_at.
 *
 * Tradeoff : pression rétention plus forte (joueur engage ses crédits) mais
 * choix de duree plus granulaire (7 a 28 jours par sauts de 7).
 */
class RefactorBankDurations extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // Vire les 6 anciens settings (3 durations + 3 rates).
        $this->db->table('game_settings')->whereIn('setting_key', [
            'bank_duration_short_days', 'bank_duration_mid_days', 'bank_duration_long_days',
            'bank_rate_short_pct',      'bank_rate_mid_pct',      'bank_rate_long_pct',
        ])->delete();

        // Insere les 4 nouveaux taux. Durees hardcodees (7/14/21/28) dans le controller.
        $this->db->table('game_settings')->insertBatch([
            ['setting_key' => 'bank_rate_7d_pct',  'value' => '5',
             'label' => 'Banque : taux 7 jours (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur 7 jours (le plus court terme).',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_14d_pct', 'value' => '12',
             'label' => 'Banque : taux 14 jours (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur 14 jours.',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_21d_pct', 'value' => '20',
             'label' => 'Banque : taux 21 jours (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur 21 jours.',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_28d_pct', 'value' => '30',
             'label' => 'Banque : taux 28 jours (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur 28 jours (le + long terme).',
             'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->whereIn('setting_key', [
            'bank_rate_7d_pct', 'bank_rate_14d_pct', 'bank_rate_21d_pct', 'bank_rate_28d_pct',
        ])->delete();
        // Restaure les anciens.
        $this->db->table('game_settings')->insertBatch([
            ['setting_key' => 'bank_duration_short_days', 'value' => '1', 'label' => 'Banque : duree court terme (jours)', 'type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_duration_mid_days',   'value' => '7', 'label' => 'Banque : duree moyen terme (jours)','type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_duration_long_days',  'value' => '30','label' => 'Banque : duree long terme (jours)', 'type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_short_pct',      'value' => '1', 'label' => 'Banque : taux court terme (%)',     'type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_mid_pct',        'value' => '8', 'label' => 'Banque : taux moyen terme (%)',     'type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_long_pct',       'value' => '40','label' => 'Banque : taux long terme (%)',      'type' => 'int', 'category' => 'eco', 'description' => '', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
