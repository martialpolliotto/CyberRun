<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatModel extends Model
{
    protected $table         = 'combats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'attacker_player_id', 'defender_player_id',
        'status',
        'attacker_hp_remaining', 'defender_hp_remaining',
        'attacker_hp_initial', 'defender_hp_initial',
        'current_turn_player_id',
        'winner_player_id',
        'post_action', 'mug_amount',
        'ended_at',
    ];

    public function findOngoingForPlayer(int $playerId): ?array
    {
        return $this->where('status', 'ongoing')
            ->groupStart()
                ->where('attacker_player_id', $playerId)
                ->orWhere('defender_player_id', $playerId)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
    }
}
