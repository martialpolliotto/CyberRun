<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Banque : depots a duree fixe avec taux d'interet.
 *
 * Modele Torn-like simplifie :
 *  - 3 durees disponibles : court (1j), moyen (7j), long (30j)
 *  - Taux d'interet sur la duree totale (ex: 1%/8%/40% au lieu d'APR annualise)
 *  - Withdraw avant maturity : seulement le principal (interet perdu) = retention pressure
 *  - Withdraw apres maturity : principal + interet complet
 *
 * Pas de status pre-calcule en BD : on derive 'matured' au read (NOW() >= matures_at).
 * Simplifie le code + evite un cron.
 */
class CreateBankDeposits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'amount'         => ['type' => 'INT', 'unsigned' => true],       // principal
            'duration_days'  => ['type' => 'INT', 'unsigned' => true],
            'interest_pct'   => ['type' => 'DECIMAL', 'constraint' => '6,2'], // ex: 8.00 = 8%
            'deposited_at'   => ['type' => 'DATETIME', 'null' => true],
            'matures_at'     => ['type' => 'DATETIME', 'null' => true],
            'withdrawn_at'   => ['type' => 'DATETIME', 'null' => true],
            'withdrawn_amount' => ['type' => 'INT', 'unsigned' => true, 'default' => 0], // ce qui a ete recu
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['player_id', 'withdrawn_at']);
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bank_deposits');

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            ['setting_key' => 'bank_duration_short_days', 'value' => '1',
             'label' => 'Banque : duree court terme (jours)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Duree du depot court terme.', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_duration_mid_days', 'value' => '7',
             'label' => 'Banque : duree moyen terme (jours)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Duree du depot moyen terme.', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_duration_long_days', 'value' => '30',
             'label' => 'Banque : duree long terme (jours)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Duree du depot long terme.', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_short_pct', 'value' => '1',
             'label' => 'Banque : taux court terme (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur la duree totale du depot court terme.',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_mid_pct', 'value' => '8',
             'label' => 'Banque : taux moyen terme (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur la duree totale du depot moyen terme.',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_rate_long_pct', 'value' => '40',
             'label' => 'Banque : taux long terme (%)', 'type' => 'int', 'category' => 'eco',
             'description' => 'Interet en pourcentage sur la duree totale du depot long terme.',
             'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'bank_max_active_deposits', 'value' => '10',
             'label' => 'Banque : max depots actifs par joueur', 'type' => 'int', 'category' => 'eco',
             'description' => 'Limite anti-spam : depots non-withdraw simultanes.',
             'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'bank_duration_short_days', 'bank_duration_mid_days', 'bank_duration_long_days',
            'bank_rate_short_pct', 'bank_rate_mid_pct', 'bank_rate_long_pct',
            'bank_max_active_deposits',
        ])->delete();
        $this->forge->dropTable('bank_deposits');
    }
}
