<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerModel extends Model
{
    protected $table         = 'players';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id',
        'level',
        'xp',
        'credits',
        'hp_current',
        'hp_max',
        'energy_current',
        'energy_max',
        'nerve_current',
        'nerve_max',
        'stat_force',
        'stat_blindage',
        'stat_reflexes',
        'stat_hack',
        'in_hospital_until',
    ];

    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }
}
