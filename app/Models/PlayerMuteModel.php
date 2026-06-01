<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Mute chat 1-to-1. Indique que $player_id ne veut pas voir les messages de
 * $muted_player_id dans son widget chat.
 *
 * Distincte de player_relations (ami/ennemi/cible) qui represente d'autres
 * relations sociales/PvP.
 */
class PlayerMuteModel extends Model
{
    protected $table         = 'player_mutes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['player_id', 'muted_player_id'];

    /**
     * Liste des player_ids que $playerId a mute.
     *
     * @return array<int, int>
     */
    public function mutedIdsFor(int $playerId): array
    {
        $rows = $this->select('muted_player_id')
            ->where('player_id', $playerId)
            ->findAll();
        return array_map(static fn($r) => (int) $r['muted_player_id'], $rows);
    }

    /** Mute idempotent (UNIQUE empeche les doublons). */
    public function mute(int $playerId, int $targetPlayerId): bool
    {
        if ($playerId === $targetPlayerId) return false;
        $existing = $this->where('player_id', $playerId)->where('muted_player_id', $targetPlayerId)->first();
        if ($existing !== null) return true;
        $this->insert(['player_id' => $playerId, 'muted_player_id' => $targetPlayerId]);
        return true;
    }

    public function unmute(int $playerId, int $targetPlayerId): void
    {
        $this->where('player_id', $playerId)
             ->where('muted_player_id', $targetPlayerId)
             ->delete();
    }
}
