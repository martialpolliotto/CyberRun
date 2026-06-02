<?php

namespace App\Services;

use App\Models\PlayerModel;
use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;

/**
 * Dailies : 3 missions rotatives par jour, choisies deterministiquement par date
 * (meme 3 missions pour tout le monde aujourd'hui = sentiment communautaire).
 *
 * Lifecycle :
 *   1. Au 1er hit /dailies du jour, ensureToday() cree 3 assignments pour le joueur.
 *   2. trackEvent() incremente progress sur les assignments matchants.
 *   3. claim() credite reward + marque claimed_at.
 *
 * Picks deterministes : `usort templates by md5(slug.date)` puis prend les 3 premiers.
 */
class DailyService
{
    private const PICKS_PER_DAY = 3;

    /** Les 3 template_id du jour, deterministe. */
    public function todayTemplateIds(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $rows = db_connect()->table('daily_mission_templates')
            ->select('id, slug')
            ->where('active', 1)
            ->get()->getResultArray();
        if (count($rows) <= self::PICKS_PER_DAY) {
            return array_map(static fn($r) => (int) $r['id'], $rows);
        }
        usort($rows, static fn($a, $b) => strcmp(
            md5($a['slug'] . '|' . $date),
            md5($b['slug'] . '|' . $date),
        ));
        return array_map(static fn($r) => (int) $r['id'], array_slice($rows, 0, self::PICKS_PER_DAY));
    }

    /** Cree les 3 daily_assignments du jour pour ce joueur s'ils n'existent pas. Idempotent. */
    public function ensureToday(int $playerId, ?string $date = null): void
    {
        $date = $date ?? date('Y-m-d');
        $db   = db_connect();
        $existingCount = $db->table('daily_assignments')
            ->where('player_id', $playerId)
            ->where('day_date',  $date)
            ->countAllResults();
        if ($existingCount > 0) return;

        $now = Time::now()->toDateTimeString();
        $rows = [];
        foreach ($this->todayTemplateIds($date) as $tid) {
            $rows[] = [
                'player_id'   => $playerId,
                'template_id' => $tid,
                'day_date'    => $date,
                'progress'    => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        if (! empty($rows)) {
            $db->table('daily_assignments')->ignore(true)->insertBatch($rows);
        }
    }

    /**
     * Liste des dailies du joueur pour aujourd'hui (avec details template).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForPlayer(int $playerId, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $this->ensureToday($playerId, $date);

        return db_connect()->table('daily_assignments da')
            ->select('da.*, t.name AS template_name, t.description AS template_description, t.objective_type, t.objective_target, t.objective_count, t.reward_credits, t.reward_xp')
            ->join('daily_mission_templates t', 't.id = da.template_id', 'inner')
            ->where('da.player_id', $playerId)
            ->where('da.day_date',  $date)
            ->orderBy('da.id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Increment progress sur tous les assignments DU JOUR qui matchent (player, event, target).
     * Appele depuis MissionModel::trackEvent en parallele du tracking des missions principales.
     */
    public function trackEvent(int $playerId, string $eventType, string $target = '*'): void
    {
        $date = date('Y-m-d');
        $db   = db_connect();

        $rows = $db->table('daily_assignments da')
            ->select('da.id, da.progress, t.objective_count')
            ->join('daily_mission_templates t', 't.id = da.template_id', 'inner')
            ->where('da.player_id', $playerId)
            ->where('da.day_date',  $date)
            ->where('da.completed_at IS NULL', null, false)
            ->where('t.objective_type', $eventType)
            ->groupStart()
                ->where('t.objective_target', '*')
                ->orWhere('t.objective_target', $target)
            ->groupEnd()
            ->get()->getResultArray();

        $now = Time::now()->toDateTimeString();
        foreach ($rows as $r) {
            $newProgress = (int) $r['progress'] + 1;
            $update = ['progress' => $newProgress, 'updated_at' => $now];
            if ($newProgress >= (int) $r['objective_count']) {
                $update['completed_at'] = $now;
            }
            $db->table('daily_assignments')->where('id', (int) $r['id'])->update($update);
        }
    }

    /**
     * Reclamer la reward d'un daily complete mais non claimed.
     * Atomique via guard `completed_at IS NOT NULL AND claimed_at IS NULL`.
     *
     * @return array{ok: bool, message: string, credits?: int, xp?: int}
     */
    public function claim(int $playerId, int $assignmentId): array
    {
        $db = db_connect();
        $row = $db->table('daily_assignments da')
            ->select('da.id, da.completed_at, da.claimed_at, t.reward_credits, t.reward_xp, t.name')
            ->join('daily_mission_templates t', 't.id = da.template_id', 'inner')
            ->where('da.id', $assignmentId)
            ->where('da.player_id', $playerId)
            ->get()->getRowArray();
        if ($row === null) {
            return ['ok' => false, 'message' => 'Daily introuvable.'];
        }
        if ($row['completed_at'] === null) {
            return ['ok' => false, 'message' => 'Daily pas encore terminee.'];
        }
        if ($row['claimed_at'] !== null) {
            return ['ok' => false, 'message' => 'Recompense deja reclamee.'];
        }

        $db->transStart();
        $db->table('daily_assignments')
            ->where('id', $assignmentId)
            ->where('claimed_at IS NULL', null, false)
            ->update([
                'claimed_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString(),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Recompense deja reclamee.'];
        }

        $credits = (int) $row['reward_credits'];
        $xp      = (int) $row['reward_xp'];
        $playerModel = model(PlayerModel::class);
        $playerModel->creditUnconditional($playerId, $credits);
        if ($xp > 0) {
            $playerModel->grantXp($playerId, $xp);
        }
        $db->transComplete();

        return [
            'ok'      => true,
            'message' => 'Recompense : +' . number_format($credits) . '¢ +' . $xp . ' XP',
            'credits' => $credits,
            'xp'      => $xp,
        ];
    }
}
