<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // on gere manuellement created_at, pas d'updated_at

    protected $allowedFields = [
        'player_id', 'category', 'action_key', 'params',
        'target_player_id', 'related_id', 'created_at',
    ];

    /** Categories canoniques (utilisees par les filtres). */
    public const CATEGORIES = [
        'crime'    => 'Crime',
        'train'    => 'Entraînement',
        'eco'      => 'Économie',
        'social'   => 'Social',
        'mission'  => 'Mission',
        'status'   => 'État',
        'level'    => 'Progression',
    ];

    /** Periodes (filtre temporel). null = pas de borne. Renvoie le SQL datetime de borne basse. */
    public static function periodBoundary(?string $period): ?string
    {
        $now = \CodeIgniter\I18n\Time::now();
        return match ($period) {
            'hour'  => $now->subHours(1)->toDateTimeString(),
            'day'   => $now->subDays(1)->toDateTimeString(),
            'week'  => $now->subDays(7)->toDateTimeString(),
            default => null,
        };
    }

    /**
     * Liste paginee + filtres pour un joueur.
     *
     * @return array{rows: array<int, array<string,mixed>>, pager: ?\CodeIgniter\Pager\PagerInterface}
     */
    public function listForPlayer(int $playerId, ?string $category = null, ?string $period = null, int $perPage = 50): array
    {
        $b = $this->select('activity_logs.*, target.user_id AS target_user_id, target_users.username AS target_username')
            ->join('players target', 'target.id = activity_logs.target_player_id', 'left')
            ->join('users target_users', 'target_users.id = target.user_id', 'left')
            ->where('activity_logs.player_id', $playerId)
            ->orderBy('activity_logs.created_at', 'DESC')
            ->orderBy('activity_logs.id', 'DESC');

        if ($category !== null && isset(self::CATEGORIES[$category])) {
            $b = $b->where('activity_logs.category', $category);
        }

        $boundary = self::periodBoundary($period);
        if ($boundary !== null) {
            $b = $b->where('activity_logs.created_at >=', $boundary);
        }

        $rows = $b->paginate($perPage);

        // Decode JSON params pour faciliter l'usage en vue.
        foreach ($rows as &$r) {
            if (! empty($r['params']) && is_string($r['params'])) {
                $decoded = json_decode($r['params'], true);
                $r['_params'] = is_array($decoded) ? $decoded : [];
            } else {
                $r['_params'] = [];
            }
        }
        unset($r);

        return ['rows' => $rows, 'pager' => $this->pager];
    }

    /**
     * Liste paginee + filtres pour l'admin : TOUS les joueurs (vs listForPlayer).
     * Filtre optionnel par username (auteur ou cible) via LIKE.
     *
     * @return array{rows: array<int, array<string,mixed>>, pager: ?\CodeIgniter\Pager\PagerInterface}
     */
    public function listAll(?string $category = null, ?string $period = null, ?string $username = null, int $perPage = 50): array
    {
        $b = $this->select('activity_logs.*,
                            author_users.username  AS author_username,
                            target_users.username  AS target_username')
            ->join('players author',         'author.id        = activity_logs.player_id',         'left')
            ->join('users   author_users',   'author_users.id  = author.user_id',                  'left')
            ->join('players target',         'target.id        = activity_logs.target_player_id', 'left')
            ->join('users   target_users',   'target_users.id  = target.user_id',                  'left')
            ->orderBy('activity_logs.created_at', 'DESC')
            ->orderBy('activity_logs.id', 'DESC');

        if ($category !== null && isset(self::CATEGORIES[$category])) {
            $b = $b->where('activity_logs.category', $category);
        }

        $boundary = self::periodBoundary($period);
        if ($boundary !== null) {
            $b = $b->where('activity_logs.created_at >=', $boundary);
        }

        if ($username !== null && $username !== '') {
            $b = $b->groupStart()
                ->like('author_users.username', $username)
                ->orLike('target_users.username', $username)
                ->groupEnd();
        }

        $rows = $b->paginate($perPage);

        foreach ($rows as &$r) {
            if (! empty($r['params']) && is_string($r['params'])) {
                $decoded = json_decode($r['params'], true);
                $r['_params'] = is_array($decoded) ? $decoded : [];
            } else {
                $r['_params'] = [];
            }
        }
        unset($r);

        return ['rows' => $rows, 'pager' => $this->pager];
    }
}
