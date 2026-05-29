<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatTurnModel extends Model
{
    protected $table         = 'combat_turns';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'combat_id', 'turn_player_id', 'action', 'hit', 'damage_dealt', 'narrative', 'created_at',
    ];

    /**
     * Tous les tours d'un combat dans l'ordre chronologique.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForCombat(int $combatId): array
    {
        return $this->where('combat_id', $combatId)->orderBy('id')->findAll();
    }
}
