<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerCrimeProgressModel extends Model
{
    protected $table         = 'player_crime_progress';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'player_id', 'category_id', 'xp', 'attempts', 'successes',
    ];

    /**
     * Lit la progression du joueur pour une categorie (cree la ligne a 0 si manquante).
     */
    public function getOrCreate(int $playerId, int $categoryId): array
    {
        $row = $this->where('player_id', $playerId)->where('category_id', $categoryId)->first();
        if ($row !== null) {
            return $row;
        }
        $this->insert([
            'player_id'   => $playerId,
            'category_id' => $categoryId,
            'xp'          => 0,
            'attempts'    => 0,
            'successes'   => 0,
        ]);
        return $this->where('player_id', $playerId)->where('category_id', $categoryId)->first();
    }

    /**
     * Lit toutes les progressions du joueur, indexees par category_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllForPlayerIndexed(int $playerId): array
    {
        $rows = $this->where('player_id', $playerId)->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[(int) $r['category_id']] = $r;
        }
        return $out;
    }
}
