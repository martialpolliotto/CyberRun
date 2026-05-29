<?php

namespace App\Services;

use App\Models\ActivityLogModel;

/**
 * Point d'entree unique pour ecrire dans le log d'activite. Utilisable depuis
 * n'importe quel model ou controller via ActivityLogger::log(...).
 *
 * action_key = clef de traduction (ex: 'Log.crime_success').
 * params = variables injectees dans la phrase (decoupage i18n).
 */
class ActivityLogger
{
    /** Ecrit une ligne de log pour le player donne. Silencieux si player_id invalide. */
    public static function log(
        int $playerId,
        string $category,
        string $actionKey,
        array $params = [],
        ?int $targetPlayerId = null,
        ?int $relatedId = null,
    ): void
    {
        if ($playerId <= 0) {
            return;
        }
        if (! isset(ActivityLogModel::CATEGORIES[$category])) {
            $category = 'status';
        }
        model(ActivityLogModel::class)->insert([
            'player_id'        => $playerId,
            'category'         => $category,
            'action_key'       => $actionKey,
            'params'           => $params !== [] ? json_encode($params, JSON_UNESCAPED_UNICODE) : null,
            'target_player_id' => $targetPlayerId,
            'related_id'       => $relatedId,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }
}
