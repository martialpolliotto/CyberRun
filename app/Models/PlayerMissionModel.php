<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerMissionModel extends Model
{
    protected $table         = 'player_missions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'player_id', 'mission_id', 'status', 'progress',
        'started_at', 'completed_at', 'claimed_at',
    ];

    public function findForPlayerMission(int $playerId, int $missionId): ?array
    {
        return $this->where('player_id', $playerId)->where('mission_id', $missionId)->first();
    }
}
