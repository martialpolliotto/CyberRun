<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marque les joueurs gérés par le BotService.
 *  - is_bot      : drapeau bool
 *  - bot_persona : type de comportement (criminel, athlete, trafiquant, lambda)
 *
 * Les bots sont des players normaux (avec user dans la table users), juste flaggés.
 * En front ils sont indistinguables des vrais joueurs.
 *
 * Seed une game_setting : bot_action_chance_pct (par defaut 30%) qui module la
 * proba qu'un bot agisse a chaque tick cron.
 */
class AddBotFieldsToPlayers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'is_bot'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'last_drug_at'],
            'bot_persona' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'is_bot'],
        ]);
        $this->forge->addKey('is_bot');

        // Setting pour moduler la frequence d'action des bots a chaque tick.
        $this->db->table('game_settings')->insert([
            'setting_key' => 'bot_action_chance_pct',
            'value'       => '30',
            'label'       => 'Bots : chance d\'agir par tick',
            'description' => 'Pourcentage de chance qu\'un bot tire une action a chaque tick cron (1 min). 30 = ~1 action toutes les 3 minutes par bot.',
            'type'        => 'int',
            'category'    => 'bots',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('players', 'bot_persona');
        $this->forge->dropColumn('players', 'is_bot');
        $this->db->table('game_settings')->where('setting_key', 'bot_action_chance_pct')->delete();
    }
}
