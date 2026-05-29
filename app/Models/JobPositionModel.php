<?php

namespace App\Models;

use CodeIgniter\Model;

class JobPositionModel extends Model
{
    protected $table         = 'job_positions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'job_id', 'rank', 'name', 'xp_required', 'daily_salary', 'perk_text',
    ];

    /** Toutes les positions d'un job, par rank croissant. */
    public function listForJob(int $jobId): array
    {
        return $this->where('job_id', $jobId)->orderBy('rank')->findAll();
    }

    /**
     * Trouve la position la plus haute qu'un joueur peut atteindre avec son XP donne.
     * Renvoie la row position (rank, name, etc.) ou null.
     */
    public function highestUnlocked(int $jobId, int $playerJobXp): ?array
    {
        return $this->where('job_id', $jobId)
            ->where('xp_required <=', $playerJobXp)
            ->orderBy('rank', 'DESC')
            ->first();
    }
}
