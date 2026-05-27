<?php

namespace App\Models;

use CodeIgniter\Model;

class FixerModel extends Model
{
    protected $table         = 'fixers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug', 'name', 'tagline', 'description',
        'image_path', 'unlock_order',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Liste tous les fixers tries par unlock_order (pour admin). */
    public function listAll(): array
    {
        return $this->orderBy('unlock_order')->orderBy('name')->findAll();
    }

    /**
     * Renvoie les fixers debloques pour ce player. Un fixer d'unlock_order N est
     * debloque si toutes les missions de tous les fixers d'unlock_order < N sont claimed.
     * Le fixer #1 est toujours debloque.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listUnlocked(int $playerId): array
    {
        $all = $this->listAll();
        if ($all === []) {
            return [];
        }

        $unlocked = [];
        $highestUnlockedOrder = 0;

        foreach ($all as $fixer) {
            $order = (int) $fixer['unlock_order'];

            if ($order <= 1) {
                $unlocked[] = $fixer;
                $highestUnlockedOrder = max($highestUnlockedOrder, $order);
                continue;
            }

            // On debloque si toutes les missions des fixers d'ordre strictement inferieur sont claimed.
            $missingClaims = $this->db->table('missions m')
                ->join('fixers f', 'f.id = m.fixer_id', 'inner')
                ->where('f.unlock_order <', $order)
                ->where('NOT EXISTS (SELECT 1 FROM player_missions pm WHERE pm.mission_id = m.id AND pm.player_id = ' . (int) $playerId . " AND pm.status = 'claimed')")
                ->countAllResults();

            if ($missingClaims === 0) {
                $unlocked[] = $fixer;
                $highestUnlockedOrder = max($highestUnlockedOrder, $order);
            }
        }

        return $unlocked;
    }
}
