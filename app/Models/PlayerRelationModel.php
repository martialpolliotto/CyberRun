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
     * Toutes les relations d'un joueur indexees par type.
     *
     * @return array{friend: array<int, array>, enemy: array<int, array>, target: array<int, array>}
     */
    public function listForPlayerGrouped(int $playerId): array
    {
        $rows = $this->select('player_relations.*, users.username AS target_username, players.level AS target_level')
            ->join('players', 'players.id = player_relations.target_player_id', 'inner')
            ->join('users',   'users.id = players.user_id', 'inner')
            ->where('player_relations.player_id', $playerId)
            ->orderBy('users.username')
            ->findAll();
        $out = ['friend' => [], 'enemy' => [], 'target' => []];
        foreach ($rows as $r) {
            $type = (string) $r['relation_type'];
            if (isset($out[$type])) {
                $out[$type][] = $r;
            }
        }
        return $out;
    }
}
