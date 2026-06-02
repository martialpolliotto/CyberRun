<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerRelationModel extends Model
{
    protected $table         = 'player_relations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['player_id', 'target_player_id', 'relation_type', 'note'];

    /** Types autorises (anti-injection des inputs). */
    public const RELATION_TYPES = ['friend' => 'Ami', 'enemy' => 'Ennemi', 'target' => 'Cible'];

    /** Indique si playerId considere targetId comme du type donne. */
    public function has(int $playerId, int $targetId, string $type): bool
    {
        return $this->where('player_id', $playerId)
            ->where('target_player_id', $targetId)
            ->where('relation_type', $type)
            ->countAllResults() > 0;
    }

    /** Toggle : si existe la supprime et renvoie 'removed', sinon insere et renvoie 'added'. */
    public function toggle(int $playerId, int $targetId, string $type): string
    {
        if (! isset(self::RELATION_TYPES[$type])) {
            return 'invalid';
        }
        if ($playerId === $targetId) {
            return 'invalid';
        }
        $existing = $this->where('player_id', $playerId)
            ->where('target_player_id', $targetId)
            ->where('relation_type', $type)
            ->first();
        if ($existing !== null) {
            $this->delete($existing['id']);
            return 'removed';
        }
        $this->insert([
            'player_id'        => $playerId,
            'target_player_id' => $targetId,
            'relation_type'    => $type,
        ]);
        return 'added';
    }

    /**
     * Toutes les relations d'un joueur indexees par type. Inclut un statut derive :
     *  - 'jail' si in_jail_until > NOW
     *  - 'hospital' si in_hospital_until > NOW
     *  - 'online' si last_seen_at > NOW - threshold
     *  - 'offline' sinon
     *
     * @return array{friend: array<int, array>, enemy: array<int, array>, target: array<int, array>}
     */
    public function listForPlayerGrouped(int $playerId, int $onlineThresholdSeconds = 300): array
    {
        $rows = $this->select('player_relations.*,
                               users.username AS target_username,
                               players.level AS target_level,
                               players.last_seen_at,
                               players.in_jail_until,
                               players.in_hospital_until')
            ->join('players', 'players.id = player_relations.target_player_id', 'inner')
            ->join('users',   'users.id = players.user_id', 'inner')
            ->where('player_relations.player_id', $playerId)
            ->orderBy('users.username')
            ->findAll();

        $now = \CodeIgniter\I18n\Time::now();
        foreach ($rows as &$r) {
            if (! empty($r['in_jail_until']) && \CodeIgniter\I18n\Time::parse($r['in_jail_until'])->isAfter($now)) {
                $r['_status'] = 'jail';
            } elseif (! empty($r['in_hospital_until']) && \CodeIgniter\I18n\Time::parse($r['in_hospital_until'])->isAfter($now)) {
                $r['_status'] = 'hospital';
            } elseif (! empty($r['last_seen_at']) && $now->getTimestamp() - \CodeIgniter\I18n\Time::parse($r['last_seen_at'])->getTimestamp() < $onlineThresholdSeconds) {
                $r['_status'] = 'online';
            } else {
                $r['_status'] = 'offline';
            }
        }
        unset($r);

        $out = ['friend' => [], 'enemy' => [], 'target' => []];
        foreach ($rows as $r) {
            $type = (string) $r['relation_type'];
            if (isset($out[$type])) {
                $out[$type][] = $r;
            }
        }
        return $out;
    }

    /** Compte les amis online (pour le badge sidebar). */
    public function countOnlineFriends(int $playerId, int $onlineThresholdSeconds = 300): int
    {
        $now = date('Y-m-d H:i:s', time() - $onlineThresholdSeconds);
        return $this->select('player_relations.id')
            ->join('players', 'players.id = player_relations.target_player_id', 'inner')
            ->where('player_relations.player_id', $playerId)
            ->where('player_relations.relation_type', 'friend')
            ->where('players.last_seen_at >', $now)
            ->countAllResults();
    }
}
