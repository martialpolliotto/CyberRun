<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Level up devient un choix explicite (style Torn).
 *
 * Avant : grantXp cascadait automatiquement les level-ups en boucle.
 * Maintenant : XP s'accumule au-dela du seuil, le joueur va sur /level-up pour
 * appliquer manuellement le passage de niveau. Bonus : +N hp_max + heal full
 * (configurable via game_settings.level_up_hp_max_bonus).
 *
 * Raison : permettre de retarder le level-up pour rester dans un palier specifique
 * (= eviter d'etre catapulte au niveau N+1 si on cherche a maximiser des objectifs
 * lies au level courant : missions, stats relatives, plus tard travel system lock).
 */
class AddLevelUpBonus extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insert([
            'setting_key' => 'level_up_hp_max_bonus',
            'value'       => '10',
            'label'       => 'Level up : bonus hp_max par niveau',
            'type'        => 'int',
            'category'    => 'progression',
            'description' => 'Points ajoutes au hp_max a chaque level-up applique manuellement. Le hp_current est aussi mis a fond.',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->where('setting_key', 'level_up_hp_max_bonus')->delete();
    }
}
