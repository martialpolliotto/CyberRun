<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerCombatStatsModel extends Model
{
    protected $table         = 'player_combat_stats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'player_id', 'attacks_won', 'attacks_lost',
        'defenses_won', 'defenses_lost',
        'kills', 'deaths', 'kill_streak', 'best_kill_streak',
    ];

    /**
     * Lit la ligne du joueur, la cree avec des zeros si manquante. Toujours non null.
     *
     * @return array<string, mixed>
     */
    public function getOrCreate(int $playerId): array
    {
        $row = $this->where('player_id', $playerId)->first();
        if ($row !== null) {
            return $row;
        }
        $this->insert(['player_id' => $playerId]);
        return $this->where('player_id', $playerId)->first();
    }
}
