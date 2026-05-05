<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

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

    /** Coût en énergie d'un entraînement (1 ligne = 1 endroit pour ajuster). */
    public const TRAIN_ENERGY_COST = 5;
    /** Gain de stat par entraînement réussi. */
    public const TRAIN_STAT_GAIN = 1;

    /** Slugs URL → noms de colonnes BDD (whitelist anti-injection). */
    public const TRAINABLE_STATS = [
        'force'    => 'stat_force',
        'blindage' => 'stat_blindage',
        'reflexes' => 'stat_reflexes',
        'hack'     => 'stat_hack',
    ];

    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Tente un entraînement de stat pour un player.
     *
     * Effectue les vérifs (slug stat valide, énergie suffisante, pas en cyberclinique)
     * puis applique l'update en une seule requête atomique pour éviter race conditions
     * (decrement énergie + increment stat conditionné sur energy_current >= cost).
     *
     * @return array{ok: bool, message: string, gain?: int, cost?: int}
     */
    public function train(int $playerId, string $statSlug): array
    {
        if (! isset(self::TRAINABLE_STATS[$statSlug])) {
            return ['ok' => false, 'message' => 'Stat inconnue.'];
        }
        $statColumn = self::TRAINABLE_STATS[$statSlug];

        $player = $this->find($playerId);
        if ($player === null) {
            return ['ok' => false, 'message' => 'Profil introuvable.'];
        }

        if (! empty($player['in_hospital_until']) && Time::parse($player['in_hospital_until'])->isAfter(Time::now())) {
            return ['ok' => false, 'message' => 'Tu es en cyberclinique, impossible d\'entraîner.'];
        }

        if ((int) $player['energy_current'] < self::TRAIN_ENERGY_COST) {
            return ['ok' => false, 'message' => 'Énergie insuffisante (' . self::TRAIN_ENERGY_COST . ' requise).'];
        }

        // Update atomique : on n'applique que si energy_current >= cost (anti race condition).
        $affected = $this->builder()
            ->where('id', $playerId)
            ->where('energy_current >=', self::TRAIN_ENERGY_COST)
            ->update([
                'energy_current' => new \CodeIgniter\Database\RawSql('energy_current - ' . self::TRAIN_ENERGY_COST),
                $statColumn      => new \CodeIgniter\Database\RawSql($statColumn . ' + ' . self::TRAIN_STAT_GAIN),
                'updated_at'     => Time::now()->toDateTimeString(),
            ]);

        if (! $affected) {
            return ['ok' => false, 'message' => 'Entraînement échoué (énergie insuffisante ?).'];
        }

        return [
            'ok'      => true,
            'message' => '+' . self::TRAIN_STAT_GAIN . ' ' . ucfirst($statSlug) . ' (-' . self::TRAIN_ENERGY_COST . ' énergie).',
            'gain'    => self::TRAIN_STAT_GAIN,
            'cost'    => self::TRAIN_ENERGY_COST,
        ];
    }
}
