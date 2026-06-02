<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Espionnage : revelation des 4 stats combat cachees (force, blindage, reflexes, hack)
 * d'une cible contre N nerve. Resultat cache N heures dans spy_attempts pour eviter
 * que chaque rechargement de la fiche du joueur facture la nerve a nouveau.
 *
 * Snapshot des stats au moment du spy : si la cible monte ses stats apres,
 * l'espionne voit l'ancienne valeur jusqu'a expiration du cache.
 */
class CreateSpyAttempts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'spy_player_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'target_player_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'stat_force'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'stat_blindage'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'stat_reflexes'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'stat_hack'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'expires_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['spy_player_id', 'target_player_id', 'expires_at']);
        $this->forge->addForeignKey('spy_player_id',    'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('spy_attempts');

        $now = date('Y-m-d H:i:s');
        $this->db->table('game_settings')->insertBatch([
            [
                'setting_key' => 'spy_nerve_cost', 'value' => '50',
                'label' => 'Espionnage : cout en nerve', 'type' => 'int', 'category' => 'combat',
                'description' => 'Nerve debitee pour reveler les 4 stats combat cachees d\'une cible.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'setting_key' => 'spy_cache_minutes', 'value' => '60',
                'label' => 'Espionnage : duree de cache du resultat (min)', 'type' => 'int', 'category' => 'combat',
                'description' => 'Durant N minutes apres un spy, recharger la fiche du joueur affiche le resultat cache sans re-debit.',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', ['spy_nerve_cost', 'spy_cache_minutes'])->delete();
        $this->forge->dropTable('spy_attempts');
    }
}
