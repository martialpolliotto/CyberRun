<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Chat en direct MVP : 1 channel global + 1 channel par faction.
 * Polling HTMX (pas de WebSocket), persistance avec prune des plus anciens
 * messages au tick cron pour eviter une table qui grossit indefiniment.
 *
 * - chat_messages : id auto-increment + channel + sender + body, indexe pour fetch
 *   chronologique "depuis id X" et purge par channel.
 * - players.chat_muted_until : mute global pose par un admin (style in_jail_until).
 * - game_settings : rate limit (3 couches), blacklist mots, retention par channel.
 */
class CreateChatTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'channel'           => ['type' => 'VARCHAR', 'constraint' => 32],
            'sender_player_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'body'              => ['type' => 'VARCHAR', 'constraint' => 500],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['channel', 'id']);
        $this->forge->addKey('sender_player_id');
        $this->forge->addForeignKey('sender_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('chat_messages');

        // players.chat_muted_until
        $this->forge->addColumn('players', [
            'chat_muted_until' => ['type' => 'DATETIME', 'null' => true, 'after' => 'in_hospital_until'],
        ]);

        $now = date('Y-m-d H:i:s');
        $settings = [
            [
                'setting_key' => 'chat_rate_hard_seconds', 'value' => '2',
                'label' => 'Chat : delai minimum entre 2 messages (s)', 'type' => 'int', 'category' => 'chat',
                'description' => 'Anti-spam basique. Rejet si dernier message envoye il y a moins de N secondes.',
            ],
            [
                'setting_key' => 'chat_rate_burst_count', 'value' => '5',
                'label' => 'Chat : max messages par fenetre courte', 'type' => 'int', 'category' => 'chat',
                'description' => 'Couche anti-burst (combine avec chat_rate_burst_seconds).',
            ],
            [
                'setting_key' => 'chat_rate_burst_seconds', 'value' => '10',
                'label' => 'Chat : fenetre anti-burst (s)', 'type' => 'int', 'category' => 'chat',
                'description' => 'Sur cette fenetre glissante, max chat_rate_burst_count messages.',
            ],
            [
                'setting_key' => 'chat_rate_soft_count', 'value' => '10',
                'label' => 'Chat : max messages par minute', 'type' => 'int', 'category' => 'chat',
                'description' => 'Couche anti-flood sur 1 minute glissante.',
            ],
            [
                'setting_key' => 'chat_rate_soft_seconds', 'value' => '60',
                'label' => 'Chat : fenetre anti-flood (s)', 'type' => 'int', 'category' => 'chat',
                'description' => 'Combine avec chat_rate_soft_count.',
            ],
            [
                'setting_key' => 'chat_msg_max_len', 'value' => '500',
                'label' => 'Chat : longueur max d\'un message', 'type' => 'int', 'category' => 'chat',
                'description' => 'Tronque ou rejette au-dela (rejette).',
            ],
            [
                'setting_key' => 'chat_blacklist_csv', 'value' => '',
                'label' => 'Chat : mots censures (CSV)', 'type' => 'string', 'category' => 'chat',
                'description' => 'Liste de mots, separes par virgule. Remplaces silencieusement par ***. Word boundaries appliques.',
            ],
            [
                'setting_key' => 'chat_history_keep_per_channel', 'value' => '500',
                'label' => 'Chat : retention par channel (lignes)', 'type' => 'int', 'category' => 'chat',
                'description' => 'Le tick cron prune les messages au-dela des N plus recents par channel.',
            ],
            [
                'setting_key' => 'chat_block_external_links', 'value' => '1',
                'label' => 'Chat : bloquer les liens externes', 'type' => 'bool', 'category' => 'chat',
                'description' => 'Si activé, regex http:// https:// www. -> message rejete avec un erreur explicite.',
            ],
        ];
        foreach ($settings as &$s) { $s['created_at'] = $now; $s['updated_at'] = $now; }
        unset($s);
        $this->db->table('game_settings')->insertBatch($settings);
    }

    public function down()
    {
        $this->db->table('game_settings')->whereIn('setting_key', [
            'chat_rate_hard_seconds', 'chat_rate_burst_count', 'chat_rate_burst_seconds',
            'chat_rate_soft_count', 'chat_rate_soft_seconds', 'chat_msg_max_len',
            'chat_blacklist_csv', 'chat_history_keep_per_channel', 'chat_block_external_links',
        ])->delete();
        $this->forge->dropColumn('players', 'chat_muted_until');
        $this->forge->dropTable('chat_messages');
    }
}
