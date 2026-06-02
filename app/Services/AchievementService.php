<?php

namespace App\Services;

use App\Models\PlayerModel;
use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;

/**
 * Service achievements/trophees.
 *
 * trackEvent : pour chaque achievement matchant le trigger, increment progress
 * dans player_achievements (insert si manquant). Quand progress >= count, unlock
 * + credit rewards.
 *
 * Appele depuis MissionModel::trackEvent (qui broadcast vers dailies aussi).
 */
class AchievementService
{
    public function trackEvent(int $playerId, string $eventType, string $target = '*'): void
    {
        $db = db_connect();

        // Trouve tous les achievements matchant (type + target='*' ou target exact)
        // pour lesquels le joueur n'a pas encore unlock.
        $achievements = $db->table('achievements a')
            ->select('a.id, a.slug, a.name, a.trigger_count, a.reward_credits, a.reward_xp')
            ->where('a.trigger_type', $eventType)
            ->groupStart()
                ->where('a.trigger_target', '*')
                ->orWhere('a.trigger_target', $target)
            ->groupEnd()
            ->get()->getResultArray();

        if (empty($achievements)) return;

        $playerModel = model(PlayerModel::class);
        $now         = Time::now()->toDateTimeString();

        foreach ($achievements as $a) {
            // Upsert player_achievements : increment progress.
            $pa = $db->table('player_achievements')
                ->where('player_id', $playerId)
                ->where('achievement_id', (int) $a['id'])
                ->get()->getRowArray();

            if ($pa === null) {
                $db->table('player_achievements')->insert([
                    'player_id'      => $playerId,
                    'achievement_id' => (int) $a['id'],
                    'progress'       => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $newProgress = 1;
                $paId = (int) $db->insertID();
            } else {
                if ($pa['unlocked_at'] !== null) continue; // deja unlock, skip
                $newProgress = (int) $pa['progress'] + 1;
                $db->table('player_achievements')
                    ->where('id', (int) $pa['id'])
                    ->update([
                        'progress'   => new RawSql('progress + 1'),
                        'updated_at' => $now,
                    ]);
                $paId = (int) $pa['id'];
            }

            // Threshold atteint : unlock + rewards.
            if ($newProgress >= (int) $a['trigger_count']) {
                $db->table('player_achievements')
                    ->where('id', $paId)
                    ->where('unlocked_at IS NULL', null, false)
                    ->update([
                        'unlocked_at' => $now,
                        'updated_at'  => $now,
                    ]);
                if ($db->affectedRows() > 0) {
                    if ((int) $a['reward_credits'] > 0) $playerModel->creditUnconditional($playerId, (int) $a['reward_credits']);
                    if ((int) $a['reward_xp'] > 0)      $playerModel->grantXp($playerId, (int) $a['reward_xp']);
                    \App\Services\ActivityLogger::log($playerId, 'level', 'Log.achievement_unlocked', [
                        'name' => $a['name'],
                    ]);
                }
            }
        }
    }

    /**
     * Liste de tous les achievements + progress du joueur (group par categorie).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function listForPlayerGrouped(int $playerId): array
    {
        $db = db_connect();
        $rows = $db->table('achievements a')
            ->select('a.*, pa.progress, pa.unlocked_at')
            ->join('player_achievements pa',
                'pa.achievement_id = a.id AND pa.player_id = ' . (int) $playerId,
                'left')
            ->orderBy('a.category')
            ->orderBy('a.sort_order')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            // Achievements 'hidden' restent caches tant que non unlock.
            if ((int) $r['hidden'] === 1 && $r['unlocked_at'] === null) continue;
            $cat = (string) $r['category'];
            $out[$cat][] = $r;
        }
        return $out;
    }

    /** Compteur (unlocked, total visibles). */
    public function counts(int $playerId): array
    {
        $grouped = $this->listForPlayerGrouped($playerId);
        $total = 0; $unlocked = 0;
        foreach ($grouped as $cat => $list) {
            foreach ($list as $r) {
                $total++;
                if ($r['unlocked_at'] !== null) $unlocked++;
            }
        }
        return ['unlocked' => $unlocked, 'total' => $total];
    }
}
