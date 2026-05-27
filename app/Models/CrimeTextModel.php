<?php

namespace App\Models;

use CodeIgniter\Model;

class CrimeTextModel extends Model
{
    protected $table         = 'crime_texts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'crime_id', 'outcome', 'text',
        'reward_credits_min', 'reward_credits_max',
        'reward_xp', 'reward_category_xp',
        'critical_destination', 'critical_minutes_min', 'critical_minutes_max',
    ];

    public const VALID_OUTCOMES = ['success', 'fail', 'critical'];

    /**
     * Toutes les variantes pour un crime, indexees par outcome puis liste.
     *
     * @return array{success: array, fail: array, critical: array}
     */
    public function listGroupedForCrime(int $crimeId): array
    {
        $rows = $this->where('crime_id', $crimeId)->orderBy('id')->findAll();
        $out  = ['success' => [], 'fail' => [], 'critical' => []];
        foreach ($rows as $r) {
            $o = (string) $r['outcome'];
            if (isset($out[$o])) {
                $out[$o][] = $r;
            }
        }
        return $out;
    }

    /**
     * Pioche une variante au hasard pour (crime, outcome). Renvoie la row complete
     * (texte + overrides eventuels) ou null si aucune variante n'existe.
     *
     * @return array<string, mixed>|null
     */
    public function pickRandom(int $crimeId, string $outcome): ?array
    {
        if (! in_array($outcome, self::VALID_OUTCOMES, true)) {
            return null;
        }
        $row = $this->where('crime_id', $crimeId)
            ->where('outcome', $outcome)
            ->orderBy('RAND()', '', false)
            ->first();
        return $row ?: null;
    }
}
