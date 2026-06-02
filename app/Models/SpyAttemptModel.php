<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * Cache des spy attempts. 1 ligne par (spy, target) qui expire apres N minutes.
 * Recharger la fiche d'un joueur deja espionne dans la fenetre retourne ce cache
 * sans nouveau debit.
 */
class SpyAttemptModel extends Model
{
    protected $table         = 'spy_attempts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'spy_player_id', 'target_player_id',
        'stat_force', 'stat_blindage', 'stat_reflexes', 'stat_hack',
        'created_at', 'expires_at',
    ];

    /**
     * Cache courant non expire pour cette paire (spy, target). Null si rien ou expire.
     *
     * @return array<string,mixed>|null
     */
    public function findActive(int $spyId, int $targetId): ?array
    {
        $row = $this->where('spy_player_id', $spyId)
            ->where('target_player_id', $targetId)
            ->where('expires_at >', Time::now()->toDateTimeString())
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }
}
