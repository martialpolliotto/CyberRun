<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Messagerie privee 1-to-1 entre joueurs. Pas de groupe, pas de live chat.
 *
 * Modele Torn mail : un message a un sender + un recipient. Les threads sont
 * derivees a l'execution en groupant les messages par paire (player, partenaire).
 * read_at = NULL tant que le destinataire n'a pas ouvert son thread.
 */
class CreateMessagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sender_player_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'recipient_player_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'body'                => ['type' => 'VARCHAR', 'constraint' => 2000],
            'read_at'             => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        // Liste des threads + compteur unread cote destinataire.
        $this->forge->addKey(['recipient_player_id', 'read_at']);
        // Recherche thread (A <-> B) ordonnee chronologiquement.
        $this->forge->addKey(['sender_player_id', 'recipient_player_id', 'created_at']);
        $this->forge->addKey(['recipient_player_id', 'sender_player_id', 'created_at']);
        $this->forge->addForeignKey('sender_player_id',    'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recipient_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('messages');
    }

    public function down()
    {
        $this->forge->dropTable('messages');
    }
}
