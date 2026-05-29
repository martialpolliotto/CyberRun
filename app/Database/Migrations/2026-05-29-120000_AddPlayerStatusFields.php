<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Champs "status" affiches dans la sidebar gauche en icones conditionnelles, facon Torn.
 *
 * - sex                 : 'm'|'f'|null
 * - job                 : nom court du job (varchar) — feature future
 * - faction_id          : FK vers une table factions a venir
 * - married_to_player_id: lien vers le conjoint (FK)
 * - is_donator          : marker statut donateur
 *
 * Toutes nullable/0 par defaut, l'UI ne montre l'icone que si la valeur existe.
 * Pour les bots, BotService::populate generera un sex random.
 */
class AddPlayerStatusFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('players', [
            'sex'                  => ['type' => 'CHAR', 'constraint' => 1, 'null' => true, 'after' => 'bot_weekend_boost_pct'],
            'job'                  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'sex'],
            'faction_id'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'job'],
            'married_to_player_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'faction_id'],
            'is_donator'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'married_to_player_id'],
        ]);

        // Backfill : assigne un sex random aux bots existants.
        $bots = $this->db->table('players')->where('is_bot', 1)->select('id')->get()->getResultArray();
        foreach ($bots as $b) {
            $this->db->table('players')->where('id', (int) $b['id'])
                ->update(['sex' => random_int(0, 1) === 0 ? 'm' : 'f']);
        }
    }

    public function down()
    {
        foreach (['sex', 'job', 'faction_id', 'married_to_player_id', 'is_donator'] as $col) {
            $this->forge->dropColumn('players', $col);
        }
    }
}
