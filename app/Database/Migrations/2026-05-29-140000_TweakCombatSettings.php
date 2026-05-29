<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajustements game design combat :
 *  - Cout par tour passe d'energie a nerve (la nerve est le rate limiter naturel).
 *  - Suppression du cooldown global entre attaques (la nerve regule deja).
 *  - Renomme combat_energy_per_turn -> combat_nerve_per_turn, valeur 1.
 *
 * Le defenseur garde toutes ses options (attack/guard/flee). L'attaquant ne peut
 * que attaquer ou fuir (game design : si tu engages, tu engages). C'est gere
 * cote service (CombatService::takeTurn refuse 'guard' pour l'attacker).
 */
class TweakCombatSettings extends Migration
{
    public function up()
    {
        $db = $this->db;
        $db->table('game_settings')->where('setting_key', 'combat_energy_per_turn')->update([
            'setting_key' => 'combat_nerve_per_turn',
            'value'       => '1',
            'label'       => 'Combat : nerve par tour',
            'description' => 'Nerve consommee a chaque action en combat. La nerve regule naturellement la frequence des attaques.',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $db->table('game_settings')->where('setting_key', 'combat_cooldown_seconds')->delete();
    }

    public function down()
    {
        $db = $this->db;
        $db->table('game_settings')->where('setting_key', 'combat_nerve_per_turn')->update([
            'setting_key' => 'combat_energy_per_turn',
            'value'       => '5',
            'label'       => 'Combat : énergie par tour',
            'description' => 'Énergie consommée par chaque action en combat.',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $db->table('game_settings')->insert([
            'setting_key' => 'combat_cooldown_seconds', 'value' => '300',
            'label' => 'Combat : cooldown entre attaques (s)', 'type' => 'int', 'category' => 'combat',
            'description' => 'Seconds entre deux attaques d\'un meme joueur.',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
