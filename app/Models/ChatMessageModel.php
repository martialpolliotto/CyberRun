<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table         = 'chat_messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'channel', 'sender_player_id', 'body',
    ];

    /**
     * Fetch des messages d'un channel depuis $sinceId (exclusif), avec usernames + faction tag.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchSince(string $channel, int $sinceId, int $limit = 100): array
    {
        return $this->select('chat_messages.*, users.username, factions.tag AS faction_tag, players.in_jail_until, players.in_hospital_until')
            ->join('players',  'players.id  = chat_messages.sender_player_id', 'inner')
            ->join('users',    'users.id    = players.user_id', 'inner')
            ->join('factions', 'factions.id = players.faction_id', 'left')
            ->where('chat_messages.channel', $channel)
            ->where('chat_messages.id >', $sinceId)
            ->orderBy('chat_messages.id', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /** Derniers N messages d'un channel (ordre chronologique). */
    public function latest(string $channel, int $limit = 50): array
    {
        $rows = $this->select('chat_messages.*, users.username, factions.tag AS faction_tag, players.in_jail_until, players.in_hospital_until')
            ->join('players',  'players.id  = chat_messages.sender_player_id', 'inner')
            ->join('users',    'users.id    = players.user_id', 'inner')
            ->join('factions', 'factions.id = players.faction_id', 'left')
            ->where('chat_messages.channel', $channel)
            ->orderBy('chat_messages.id', 'DESC')
            ->limit($limit)
            ->findAll();
        return array_reverse($rows);
    }

    /** Nombre de messages envoyes par un joueur dans les N dernieres secondes (toutes channels). */
    public function recentSendCount(int $playerId, int $withinSeconds): int
    {
        return $this->where('sender_player_id', $playerId)
            ->where('created_at >', date('Y-m-d H:i:s', time() - $withinSeconds))
            ->countAllResults();
    }

    /**
     * Prune les messages au-dela des $keep plus recents pour un channel donne.
     * Retourne le nombre de lignes supprimees.
     */
    public function pruneChannel(string $channel, int $keep): int
    {
        if ($keep <= 0) return 0;

        $row = $this->select('id')
            ->where('channel', $channel)
            ->orderBy('id', 'DESC')
            ->limit(1, $keep - 1) // ($keep - 1)e plus recent depuis 0 = $keep eme row
            ->first();
        if ($row === null) return 0;
        $cutoffId = (int) $row['id'];

        $db = db_connect();
        $db->table($this->table)
            ->where('channel', $channel)
            ->where('id <', $cutoffId)
            ->delete();
        return (int) $db->affectedRows();
    }

    /**
     * Liste des channels actifs (= ayant au moins un message), pour le cron prune.
     *
     * @return array<int, string>
     */
    public function listActiveChannels(): array
    {
        $rows = $this->select('channel')->groupBy('channel')->findAll();
        return array_map(static fn($r) => (string) $r['channel'], $rows);
    }
}
