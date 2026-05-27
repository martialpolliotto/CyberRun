<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class MissionModel extends Model
{
    protected $table         = 'missions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'fixer_id', 'slug', 'name', 'brief', 'outro', 'mission_order',
        'objective_type', 'objective_target', 'objective_count',
        'reward_credits', 'reward_xp', 'reward_item_id',
    ];

    /** Types d'objectif supportes. Le tracking n'agit que sur cette liste. */
    public const OBJECTIVE_TYPES = [
        'visit_page'    => 'Visiter une page',
        'train_stat'    => 'Entrainer une stat (compteur)',
        'reach_stat'    => 'Atteindre une valeur de stat (seuil)',
        'reach_level'   => 'Atteindre un niveau (seuil)',
        'buy_item'      => 'Acheter un item',
        'equip_slot'    => 'Equiper un slot',
        'spend_credits' => 'Depenser N credits cumules',
        'commit_crime'  => 'Reussir un crime (slug crime ou slug categorie)',
    ];

    /** Types ou objective_target n'a pas de sens (any). */
    public const TYPES_WITHOUT_TARGET = ['reach_level'];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Toutes les missions d'un fixer, ordonnees. */
    public function listForFixer(int $fixerId): array
    {
        return $this->where('fixer_id', $fixerId)->orderBy('mission_order')->findAll();
    }

    /**
     * Mission "active" affichee sur la page d'un fixer pour un player.
     * Renvoie la 1ere mission (ordre mission_order) qui n'est pas claimed.
     *
     * Si toutes les missions du fixer sont claimed, renvoie null (chaine terminee).
     */
    public function getCurrentMissionForPlayer(int $fixerId, int $playerId): ?array
    {
        return $this->select('missions.*, pm.status AS player_status, pm.progress AS player_progress, pm.id AS player_mission_id')
            ->join('player_missions pm', 'pm.mission_id = missions.id AND pm.player_id = ' . (int) $playerId, 'left')
            ->where('missions.fixer_id', $fixerId)
            ->groupStart()
                ->where('pm.status IS NULL')
                ->orWhereIn('pm.status', ['in_progress', 'completed'])
            ->groupEnd()
            ->orderBy('missions.mission_order')
            ->first();
    }

    /**
     * Accepte une mission : cree un player_mission status=in_progress, progress=0.
     *
     * @return array{ok: bool, message: string}
     */
    public function accept(int $playerId, int $missionId): array
    {
        $mission = $this->find($missionId);
        if ($mission === null) {
            return ['ok' => false, 'message' => 'Mission introuvable.'];
        }

        $pmModel = model(PlayerMissionModel::class);
        $existing = $pmModel->findForPlayerMission($playerId, $missionId);
        if ($existing !== null) {
            return ['ok' => false, 'message' => 'Mission deja prise.'];
        }

        // On verifie que les missions precedentes du meme fixer sont toutes claimed.
        $blocking = $this->db->table('missions m')
            ->where('m.fixer_id', (int) $mission['fixer_id'])
            ->where('m.mission_order <', (int) $mission['mission_order'])
            ->where('NOT EXISTS (SELECT 1 FROM player_missions pm WHERE pm.mission_id = m.id AND pm.player_id = ' . (int) $playerId . " AND pm.status = 'claimed')")
            ->countAllResults();
        if ($blocking > 0) {
            return ['ok' => false, 'message' => 'Termine la mission precedente d\'abord.'];
        }

        $now = Time::now()->toDateTimeString();
        $pmModel->insert([
            'player_id'  => $playerId,
            'mission_id' => $missionId,
            'status'     => 'in_progress',
            'progress'   => 0,
            'started_at' => $now,
        ]);

        // Pour les seuils (reach_stat / reach_level), on peut deja remplir si l'etat actuel suffit.
        $this->recheckThresholdsForPlayer($playerId);

        return ['ok' => true, 'message' => 'Mission acceptee : ' . $mission['name'] . '.'];
    }

    /**
     * Reclame la recompense d'une mission completee. Marque claimed et donne credits/xp/item.
     *
     * @return array{ok: bool, message: string}
     */
    public function claim(int $playerId, int $missionId): array
    {
        $mission = $this->find($missionId);
        if ($mission === null) {
            return ['ok' => false, 'message' => 'Mission introuvable.'];
        }

        $pmModel = model(PlayerMissionModel::class);
        $pm = $pmModel->findForPlayerMission($playerId, $missionId);
        if ($pm === null || $pm['status'] !== 'completed') {
            return ['ok' => false, 'message' => 'Mission pas encore terminee.'];
        }

        $playerModel = model(PlayerModel::class);
        $rewardParts = [];

        $db = db_connect();
        $db->transStart();

        // Credits
        if ((int) $mission['reward_credits'] > 0) {
            $playerModel->builder()
                ->where('id', $playerId)
                ->update([
                    'credits'    => new \CodeIgniter\Database\RawSql('credits + ' . (int) $mission['reward_credits']),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $rewardParts[] = '+' . (int) $mission['reward_credits'] . ' credits';
        }

        // XP + level-up cascade
        if ((int) $mission['reward_xp'] > 0) {
            $playerModel->grantXp($playerId, (int) $mission['reward_xp']);
            $rewardParts[] = '+' . (int) $mission['reward_xp'] . ' XP';
        }

        // Item (optionnel)
        if (! empty($mission['reward_item_id'])) {
            model(PlayerItemModel::class)->insert([
                'player_id' => $playerId,
                'item_id'   => (int) $mission['reward_item_id'],
                'equipped'  => 0,
                'quantity'  => 1,
            ]);
            $item = model(ItemModel::class)->find((int) $mission['reward_item_id']);
            if ($item !== null) {
                $rewardParts[] = 'item : ' . $item['name'];
            }
        }

        // Marque comme claimed
        $pmModel->update($pm['id'], [
            'status'     => 'claimed',
            'claimed_at' => Time::now()->toDateTimeString(),
        ]);

        $db->transComplete();

        $msg = 'Recompense recue : ' . ($rewardParts === [] ? 'aucune' : implode(', ', $rewardParts)) . '.';
        return ['ok' => true, 'message' => $msg];
    }

    /**
     * Incremente le progress des missions in_progress du player qui matchent (type, target).
     * '*' en target sur la mission = match toutes les valeurs.
     * Si progress atteint objective_count -> status=completed.
     *
     * Appele depuis les controllers apres chaque action significative.
     */
    public function trackEvent(int $playerId, string $eventType, string $target = '*'): void
    {
        $pmModel = model(PlayerMissionModel::class);

        $rows = $pmModel->select('player_missions.*, missions.objective_count, missions.objective_target, missions.objective_type')
            ->join('missions', 'missions.id = player_missions.mission_id', 'inner')
            ->where('player_missions.player_id', $playerId)
            ->where('player_missions.status', 'in_progress')
            ->where('missions.objective_type', $eventType)
            ->groupStart()
                ->where('missions.objective_target', '*')
                ->orWhere('missions.objective_target', $target)
            ->groupEnd()
            ->findAll();

        $now = Time::now()->toDateTimeString();
        foreach ($rows as $pm) {
            $newProgress = (int) $pm['progress'] + 1;
            $update = ['progress' => $newProgress];
            if ($newProgress >= (int) $pm['objective_count']) {
                $update['status']       = 'completed';
                $update['completed_at'] = $now;
            }
            $pmModel->update($pm['id'], $update);
        }
    }

    /**
     * Re-evalue les missions in_progress de type seuil (reach_stat, reach_level) pour ce player.
     * Marque completed si la valeur courante atteint objective_count.
     *
     * Appele a chaque trackEvent + apres tout changement de stat/level.
     */
    public function recheckThresholdsForPlayer(int $playerId): void
    {
        $player = model(PlayerModel::class)->find($playerId);
        if ($player === null) {
            return;
        }

        $pmModel = model(PlayerMissionModel::class);
        $rows = $pmModel->select('player_missions.*, missions.objective_count, missions.objective_target, missions.objective_type')
            ->join('missions', 'missions.id = player_missions.mission_id', 'inner')
            ->where('player_missions.player_id', $playerId)
            ->where('player_missions.status', 'in_progress')
            ->whereIn('missions.objective_type', ['reach_stat', 'reach_level'])
            ->findAll();

        $now = Time::now()->toDateTimeString();
        foreach ($rows as $pm) {
            $currentValue = $this->valueForThreshold($player, (string) $pm['objective_type'], (string) $pm['objective_target']);
            if ($currentValue >= (int) $pm['objective_count']) {
                $pmModel->update($pm['id'], [
                    'progress'     => $currentValue,
                    'status'       => 'completed',
                    'completed_at' => $now,
                ]);
            } else {
                // On garde le progress a jour pour l'affichage.
                $pmModel->update($pm['id'], ['progress' => $currentValue]);
            }
        }
    }

    /** @param array<string, mixed> $player */
    private function valueForThreshold(array $player, string $type, string $target): int
    {
        if ($type === 'reach_level') {
            return (int) ($player['level'] ?? 0);
        }
        if ($type === 'reach_stat') {
            $col = PlayerModel::TRAINABLE_STATS[$target] ?? null;
            if ($col === null) {
                return 0;
            }
            return (int) ($player[$col] ?? 0);
        }
        return 0;
    }
}
