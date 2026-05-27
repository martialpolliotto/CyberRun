<?php

namespace App\Models;

use CodeIgniter\Model;

class CrimeTextModel extends Model
{
    protected $table         = 'crime_texts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['crime_id', 'outcome', 'text'];

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
     * Pioche une variante au hasard pour (crime, outcome). Renvoie null si aucune n'existe.
     */
    public function pickRandom(int $crimeId, string $outcome): ?string
    {
        if (! in_array($outcome, self::VALID_OUTCOMES, true)) {
            return null;
        }
        $row = $this->where('crime_id', $crimeId)
            ->where('outcome', $outcome)
            ->orderBy('RAND()', '', false)
            ->first();
        return $row['text'] ?? null;
    }
}
