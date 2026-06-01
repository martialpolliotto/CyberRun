<?php

namespace App\Services;

use App\Models\BountyModel;
use App\Models\CombatModel;
use App\Models\CombatTurnModel;
use App\Models\GameSettingModel;
use App\Models\PlayerCombatStatsModel;
use App\Models\PlayerModel;
use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;

/**
 * Moteur de combat V2 simplifie : tour-par-tour, 3 actions (attaque, garde, fuite).
 *
 * Cycle :
 *  - initiate(attackerId, targetId) cree un combat ongoing, snapshot HP.
 *  - takeTurn(combatId, playerId, action) applique l'action du joueur courant.
 *    Si l'adversaire est un bot, takeAutoTurn lui est appelee dans la foulee.
 *  - Quand HP de l'un atteint 0 ou qu'un joueur fuit avec succes, le combat ended.
 *  - Le vainqueur choisit ensuite un post_action (mug / hospitalize / leave).
 *  - resolveEnd applique les HP / hospital / bounty claim.
 */
class CombatService
{
    public const ACTIONS = ['attack', 'guard', 'flee'];

    public function settings(): GameSettingModel
    {
        return model(GameSettingModel::class);
    }

    /**
     * Initialise un combat. Renvoie ['ok'=>bool, 'message'=>str, 'combat_id'=>int?].
     */
    public function initiate(int $attackerId, int $defenderId): array
    {
        if ($attackerId === $defenderId) {
            return ['ok' => false, 'message' => 'Tu ne peux pas te combattre toi-meme.'];
        }

        $playerModel = model(PlayerModel::class);
        $attacker = $playerModel->find($attackerId);
        $defender = $playerModel->find($defenderId);
        if ($attacker === null || $defender === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }

        $now = Time::now();

        // Etats bloquants : prison/hopital des 2 cotes.
        if (! empty($attacker['in_jail_until']) && Time::parse($attacker['in_jail_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es en prison.'];
        }
        if (! empty($attacker['in_hospital_until']) && Time::parse($attacker['in_hospital_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es a la cyberclinique.'];
        }
        if (! empty($defender['in_jail_until']) && Time::parse($defender['in_jail_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'La cible est en prison, intouchable.'];
        }
        if (! empty($defender['in_hospital_until']) && Time::parse($defender['in_hospital_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'La cible est a la cyberclinique, intouchable.'];
        }

        // Un combat ongoing pour l'attacker ? S'il existe deja, le reprendre.
        $existing = model(CombatModel::class)->findOngoingForPlayer($attackerId);
        if ($existing !== null) {
            return ['ok' => true, 'message' => 'Tu as deja un combat en cours.', 'combat_id' => (int) $existing['id']];
        }

        // Cout en nerve au moment de l'engagement (rien pendant le combat lui-meme).
        $nerveCost = (int) $this->settings()->get('combat_nerve_to_start', 25);
        if ((int) $attacker['nerve_current'] < $nerveCost) {
            return ['ok' => false, 'message' => 'Pas assez de nerve : ' . $nerveCost . ' requise pour engager le combat.'];
        }
        // Transaction : debit nerve + insert combat sont 1 unite atomique.
        // Sans ca, un crash sur l'insert combat perd la nerve sans rollback.
        $db = db_connect();
        $db->transStart();

        $playerModel->builder()
            ->where('id', $attackerId)
            ->where('nerve_current >=', $nerveCost)
            ->update([
                'nerve_current'  => new RawSql('nerve_current - ' . $nerveCost),
                'last_combat_at' => $now->toDateTimeString(),
                'updated_at'     => $now->toDateTimeString(),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Nerve insuffisante au moment de l\'engagement.'];
        }

        $combatId = model(CombatModel::class)->insert([
            'attacker_player_id'     => $attackerId,
            'defender_player_id'     => $defenderId,
            'status'                 => 'ongoing',
            'attacker_hp_remaining'  => (int) $attacker['hp_current'],
            'defender_hp_remaining'  => (int) $defender['hp_current'],
            'attacker_hp_initial'    => (int) $attacker['hp_current'],
            'defender_hp_initial'    => (int) $defender['hp_current'],
            'current_turn_player_id' => $attackerId,
        ]);

        $db->transComplete();
        if (! $db->transStatus()) {
            return ['ok' => false, 'message' => 'Erreur lors de l\'engagement du combat.'];
        }

        return ['ok' => true, 'message' => 'Combat engage !', 'combat_id' => (int) $combatId];
    }

    /**
     * Joue le tour du joueur. Si la cible est un bot, joue automatiquement son tour ensuite.
     */
    public function takeTurn(int $combatId, int $playerId, string $action): array
    {
        if (! in_array($action, self::ACTIONS, true)) {
            return ['ok' => false, 'message' => 'Action invalide.'];
        }

        $combat = model(CombatModel::class)->find($combatId);
        if ($combat === null || $combat['status'] !== 'ongoing') {
            return ['ok' => false, 'message' => 'Combat introuvable ou termine.'];
        }
        if ((int) $combat['current_turn_player_id'] !== $playerId) {
            return ['ok' => false, 'message' => 'Ce n\'est pas ton tour.'];
        }

        // Garde reservee au defenseur : l'attaquant a engage le combat, il doit s'y tenir.
        $isAttacker = (int) $combat['attacker_player_id'] === $playerId;
        if ($action === 'guard' && $isAttacker) {
            return ['ok' => false, 'message' => 'L\'attaquant ne peut pas se mettre en garde, seulement attaquer ou fuir.'];
        }

        // Pas de cout par tour : la nerve est debitee au lancement du combat (initiate).
        $playerModel = model(PlayerModel::class);

        // Joue le tour.
        $this->resolveTurn($combat, $playerId, $action);

        // Si combat toujours ongoing, ce sera au tour de l'adversaire (auto si bot).
        $combat = model(CombatModel::class)->find($combatId);
        if ($combat['status'] === 'ongoing') {
            $opponentId = (int) $combat['current_turn_player_id'];
            $opponent = $playerModel->find($opponentId);
            if (! empty($opponent['is_bot'])) {
                // Bot agit automatiquement. Lui consomme aussi de l'energie virtuelle (regen tick suffira).
                $botAction = $this->pickBotAction($combat, $opponent);
                $this->resolveTurn(model(CombatModel::class)->find($combatId), $opponentId, $botAction);
            }
        }

        return ['ok' => true, 'message' => 'Tour joue.', 'combat_id' => $combatId];
    }

    /** Applique un tour au combat (calculs hit/damage/narrative + alterne le tour ou met fin). */
    private function resolveTurn(array $combat, int $playerId, string $action): void
    {
        $combatModel = model(CombatModel::class);
        $turnModel   = model(CombatTurnModel::class);
        $playerModel = model(PlayerModel::class);

        $isAttacker = (int) $combat['attacker_player_id'] === $playerId;
        $opponentId = $isAttacker ? (int) $combat['defender_player_id'] : (int) $combat['attacker_player_id'];

        $player   = $playerModel->find($playerId);
        $opponent = $playerModel->find($opponentId);

        // Stats effectives (incluent equipement + effets actifs + malus addiction).
        $myStats  = $playerModel->getEffectiveStats($playerId)['total'];
        $oppStats = $playerModel->getEffectiveStats($opponentId)['total'];

        $hit = 0; $damage = 0; $narrative = '';
        $update = ['updated_at' => date('Y-m-d H:i:s')];

        if ($action === 'attack') {
            $baseHit = (int) $this->settings()->get('combat_base_hit_pct', 70);
            // Hit% = base + my_reflexes/10 - opp_reflexes/20, clamp 30-95
            $hitPct = $baseHit + (int) round($myStats['reflexes'] / 10) - (int) round($oppStats['reflexes'] / 20);
            $hitPct = max(30, min(95, $hitPct));
            $roll   = random_int(0, 99);
            if ($roll < $hitPct) {
                $hit = 1;
                // Damage = force × (0.8 to 1.2) - opp_blindage/2, min combat_min_damage.
                $raw = $myStats['force'] * (80 + random_int(0, 40)) / 100;
                $reduction = $oppStats['blindage'] / 2.0;
                // Si l'adversaire a "garde" lors de son dernier tour, on prend en compte (regle simple :
                // le dernier tour de l'opponent dans cette table).
                if ($this->lastOpponentGuarded($combat['id'], $opponentId)) {
                    $reduction += $raw * ((int) $this->settings()->get('combat_guard_reduction_pct', 50) / 100);
                }
                $damage = max((int) $this->settings()->get('combat_min_damage', 5), (int) round($raw - $reduction));
                $narrative = 'Attaque qui touche pour ' . $damage . ' degats.';

                // Applique HP au combat (pas au joueur).
                if ($isAttacker) {
                    $newHp = max(0, (int) $combat['defender_hp_remaining'] - $damage);
                    $update['defender_hp_remaining'] = $newHp;
                } else {
                    $newHp = max(0, (int) $combat['attacker_hp_remaining'] - $damage);
                    $update['attacker_hp_remaining'] = $newHp;
                }
            } else {
                $narrative = 'Attaque mais rate.';
            }
        } elseif ($action === 'guard') {
            $narrative = 'Se met en garde.';
        } elseif ($action === 'flee') {
            $base = (int) $this->settings()->get('combat_flee_base_pct', 40);
            $fleePct = $base + (int) round($myStats['reflexes'] / 10) - (int) round($oppStats['reflexes'] / 20);
            $fleePct = max(10, min(90, $fleePct));
            if (random_int(0, 99) < $fleePct) {
                // Fuite reussie. Ce joueur fuit → combat ended sans vainqueur (l'autre garde son HP).
                $hit = 0;
                $narrative = 'Fuite reussie. Combat termine.';
                $update['status']   = $isAttacker ? 'ended_attacker_fled' : 'ended_defender_fled';
                $update['ended_at'] = date('Y-m-d H:i:s');
                $turnModel->insert([
                    'combat_id'      => (int) $combat['id'],
                    'turn_player_id' => $playerId,
                    'action'         => $action,
                    'hit'            => 0,
                    'damage_dealt'   => 0,
                    'narrative'      => $narrative,
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
                $combatModel->update((int) $combat['id'], $update);
                $this->onCombatEnded((int) $combat['id']);
                return;
            }
            $narrative = 'Tentative de fuite ratee.';
        }

        // Enregistre le tour.
        $turnModel->insert([
            'combat_id'      => (int) $combat['id'],
            'turn_player_id' => $playerId,
            'action'         => $action,
            'hit'            => $hit,
            'damage_dealt'   => $damage,
            'narrative'      => $narrative,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Verifie fin de combat par KO.
        $atkHp = $update['attacker_hp_remaining'] ?? (int) $combat['attacker_hp_remaining'];
        $defHp = $update['defender_hp_remaining'] ?? (int) $combat['defender_hp_remaining'];
        if ($defHp <= 0) {
            $update['status']           = 'ended_attacker_won';
            $update['winner_player_id'] = (int) $combat['attacker_player_id'];
            $update['ended_at']         = date('Y-m-d H:i:s');
        } elseif ($atkHp <= 0) {
            $update['status']           = 'ended_defender_won';
            $update['winner_player_id'] = (int) $combat['defender_player_id'];
            $update['ended_at']         = date('Y-m-d H:i:s');
        } else {
            // Alterne le tour.
            $update['current_turn_player_id'] = $opponentId;
        }
        $combatModel->update((int) $combat['id'], $update);

        if (isset($update['status']) && $update['status'] !== 'ongoing') {
            $this->onCombatEnded((int) $combat['id']);
        }
    }

    /** Vrai si le dernier tour de $opponentId dans ce combat etait 'guard'. */
    private function lastOpponentGuarded(int $combatId, int $opponentId): bool
    {
        $row = model(CombatTurnModel::class)
            ->where('combat_id', $combatId)
            ->where('turn_player_id', $opponentId)
            ->orderBy('id', 'DESC')
            ->first();
        return $row !== null && $row['action'] === 'guard';
    }

    /** Choix d'action automatique du bot. Strategy : attaque, sauf si HP < 30% alors essai fuite. */
    private function pickBotAction(array $combat, array $bot): string
    {
        $isAttacker = (int) $combat['attacker_player_id'] === (int) $bot['id'];
        $myHp       = $isAttacker ? (int) $combat['attacker_hp_remaining'] : (int) $combat['defender_hp_remaining'];
        $myHpInit   = $isAttacker ? (int) $combat['attacker_hp_initial'] : (int) $combat['defender_hp_initial'];
        $ratio      = $myHpInit > 0 ? $myHp / $myHpInit : 1.0;

        if ($ratio < 0.3 && random_int(0, 1) === 0) return 'flee';
        if ($ratio < 0.5 && random_int(0, 99) < 30) return 'guard';
        return 'attack';
    }

    /**
     * Quand un combat passe en ended_* (KO ou fuite), maj stats / HP joueurs / activity log.
     * Le post-action (mug/hospitalize) reste a choisir par le vainqueur ensuite.
     */
    private function onCombatEnded(int $combatId): void
    {
        $combat = model(CombatModel::class)->find($combatId);
        if ($combat === null) return;

        $playerModel = model(PlayerModel::class);
        $statsModel  = model(PlayerCombatStatsModel::class);

        // Synchronise les HP des 2 joueurs aux valeurs combat (mais ne descend pas en dessous de 0).
        foreach ([
            (int) $combat['attacker_player_id'] => (int) $combat['attacker_hp_remaining'],
            (int) $combat['defender_player_id'] => (int) $combat['defender_hp_remaining'],
        ] as $pid => $newHp) {
            $playerModel->update($pid, [
                'hp_current'     => max(0, $newHp),
                'last_combat_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Maj stats combat selon issue.
        $atk = $statsModel->getOrCreate((int) $combat['attacker_player_id']);
        $def = $statsModel->getOrCreate((int) $combat['defender_player_id']);

        $status = $combat['status'];
        if ($status === 'ended_attacker_won') {
            $statsModel->update($atk['id'], [
                'attacks_won' => new RawSql('attacks_won + 1'),
                'kill_streak' => new RawSql('kill_streak + 1'),
                'best_kill_streak' => new RawSql('GREATEST(best_kill_streak, kill_streak + 1)'),
            ]);
            $statsModel->update($def['id'], [
                'defenses_lost' => new RawSql('defenses_lost + 1'),
                'kill_streak'   => 0,
            ]);
        } elseif ($status === 'ended_defender_won') {
            $statsModel->update($def['id'], [
                'defenses_won' => new RawSql('defenses_won + 1'),
                'kill_streak'  => new RawSql('kill_streak + 1'),
                'best_kill_streak' => new RawSql('GREATEST(best_kill_streak, kill_streak + 1)'),
            ]);
            $statsModel->update($atk['id'], [
                'attacks_lost' => new RawSql('attacks_lost + 1'),
                'kill_streak'  => 0,
            ]);
        }
        // Fuites : pas de compteur attaques/defenses ajuste, juste le streak reset cote fuyard.
        if ($status === 'ended_attacker_fled') {
            $statsModel->update($atk['id'], ['kill_streak' => 0]);
        }
        if ($status === 'ended_defender_fled') {
            $statsModel->update($def['id'], ['kill_streak' => 0]);
        }

        // XP au vainqueur.
        if (! empty($combat['winner_player_id'])) {
            $xp = (int) $this->settings()->get('combat_xp_reward', 20);
            $playerModel->grantXp((int) $combat['winner_player_id'], $xp);
        }

        // Activity log
        $atkUsername = $this->resolveUsername((int) $combat['attacker_player_id']);
        $defUsername = $this->resolveUsername((int) $combat['defender_player_id']);

        if ($status === 'ended_attacker_won') {
            ActivityLogger::log((int) $combat['attacker_player_id'], 'social', 'Log.combat_won_attacker',
                ['target' => $defUsername], (int) $combat['defender_player_id'], $combatId);
            ActivityLogger::log((int) $combat['defender_player_id'], 'social', 'Log.combat_lost_defender',
                ['author' => $atkUsername], (int) $combat['attacker_player_id'], $combatId);
        } elseif ($status === 'ended_defender_won') {
            ActivityLogger::log((int) $combat['defender_player_id'], 'social', 'Log.combat_won_defender',
                ['author' => $atkUsername], (int) $combat['attacker_player_id'], $combatId);
            ActivityLogger::log((int) $combat['attacker_player_id'], 'social', 'Log.combat_lost_attacker',
                ['target' => $defUsername], (int) $combat['defender_player_id'], $combatId);
        } elseif ($status === 'ended_attacker_fled') {
            ActivityLogger::log((int) $combat['attacker_player_id'], 'social', 'Log.combat_fled',
                ['target' => $defUsername], (int) $combat['defender_player_id'], $combatId);
        } elseif ($status === 'ended_defender_fled') {
            ActivityLogger::log((int) $combat['defender_player_id'], 'social', 'Log.combat_fled',
                ['target' => $atkUsername], (int) $combat['attacker_player_id'], $combatId);
        }
    }

    /**
     * Vainqueur post-action : Mug (vol credits), Hospitalize (hopital cible + bounty claim), Leave (rien).
     */
    public function postAction(int $combatId, int $playerId, string $action): array
    {
        if (! in_array($action, ['mug', 'hospitalize', 'leave'], true)) {
            return ['ok' => false, 'message' => 'Action invalide.'];
        }

        $combatModel = model(CombatModel::class);
        $playerModel = model(PlayerModel::class);
        $db          = db_connect();

        // Verrouille la ligne combat pour empecher 2 postActions concurrents (double mug).
        $db->transStart();
        $combatModel->builder()
            ->where('id', $combatId)
            ->where('winner_player_id', $playerId)
            ->where('status !=', 'resolved')
            ->update([
                'post_action' => $action,
                'status'      => 'resolved',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Combat introuvable, deja resolu ou tu n\'es pas le vainqueur.'];
        }

        // Charge le combat (post-update) pour recuperer attacker/defender ids.
        $combat = $combatModel->find($combatId);
        $loserId = (int) $combat['winner_player_id'] === (int) $combat['attacker_player_id']
            ? (int) $combat['defender_player_id']
            : (int) $combat['attacker_player_id'];

        $msg = '';

        if ($action === 'mug') {
            $loser  = $playerModel->find($loserId);
            $mugPct = (int) $this->settings()->get('combat_mug_pct', 20);
            $stolen = (int) floor((int) $loser['credits'] * $mugPct / 100);
            if ($stolen > 0) {
                // Debit atomique du perdant : guard `credits >= stolen` evite BIGINT UNSIGNED underflow
                // si le perdant a depense des credits entre notre read et notre write.
                $playerModel->builder()
                    ->where('id', $loserId)
                    ->where('credits >=', $stolen)
                    ->update([
                        'credits'    => new RawSql('credits - ' . $stolen),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                $actuallyStolen = $db->affectedRows() > 0 ? $stolen : 0;
                if ($actuallyStolen > 0) {
                    $playerModel->builder()->where('id', $playerId)
                        ->update(['credits' => new RawSql('credits + ' . $actuallyStolen), 'updated_at' => date('Y-m-d H:i:s')]);
                    $combatModel->update($combatId, ['mug_amount' => $actuallyStolen]);
                }
                $stolen = $actuallyStolen;
            }
            $msg = 'Tu as derobe ' . number_format($stolen) . ' credits.';

            ActivityLogger::log($playerId, 'eco', 'Log.combat_mug',
                ['target' => $this->resolveUsername($loserId), 'amount' => $stolen], $loserId, $combatId);
        } elseif ($action === 'hospitalize') {
            $minM = (int) $this->settings()->get('combat_hospital_min', 10);
            $maxM = max($minM, (int) $this->settings()->get('combat_hospital_max', 30));
            $minutes = random_int($minM, $maxM);
            $playerModel->update($loserId, [
                'in_hospital_until' => Time::now()->addMinutes($minutes)->toDateTimeString(),
            ]);
            $msg = 'Tu envoies ta victime a la cyberclinique pour ' . $minutes . ' minutes.';

            // Bounty claim atomique : update WHERE status=active, lit affectedRows
            // pour eviter double-payout si la bounty a ete claimee par un autre flux.
            $bountyModel = model(BountyModel::class);
            $bounties    = $bountyModel->where('target_player_id', $loserId)->where('status', 'active')->findAll();
            $totalBounty = 0;
            foreach ($bounties as $b) {
                $bountyModel->builder()
                    ->where('id', (int) $b['id'])
                    ->where('status', 'active')
                    ->update([
                        'status'                => 'claimed',
                        'claimed_by_player_id'  => $playerId,
                        'claimed_at'            => Time::now()->toDateTimeString(),
                        'updated_at'            => date('Y-m-d H:i:s'),
                    ]);
                if ($db->affectedRows() > 0) {
                    $totalBounty += (int) $b['amount'];
                }
            }
            if ($totalBounty > 0) {
                $playerModel->builder()->where('id', $playerId)
                    ->update(['credits' => new RawSql('credits + ' . $totalBounty), 'updated_at' => date('Y-m-d H:i:s')]);
                $msg .= ' Bounty claim : +' . number_format($totalBounty) . ' credits encaisses.';
                ActivityLogger::log($playerId, 'eco', 'Log.bounty_claimed',
                    ['target' => $this->resolveUsername($loserId), 'amount' => $totalBounty], $loserId);
            }

            // Maj compteur kills cote attacker, deaths cote loser.
            $statsModel = model(PlayerCombatStatsModel::class);
            $statsModel->update($statsModel->getOrCreate($playerId)['id'], ['kills' => new RawSql('kills + 1')]);
            $statsModel->update($statsModel->getOrCreate($loserId)['id'], ['deaths' => new RawSql('deaths + 1'), 'kill_streak' => 0]);

            ActivityLogger::log($playerId, 'social', 'Log.combat_hospitalize',
                ['target' => $this->resolveUsername($loserId), 'minutes' => $minutes], $loserId, $combatId);

            // Hook faction : respect gagne si l'attaquant est membre.
            $attacker = $playerModel->find($playerId);
            if ($attacker !== null && ! empty($attacker['faction_id'])) {
                $gain = (int) $this->settings()->get('faction_respect_per_hospitalize', 5);
                if ($gain > 0) {
                    model(\App\Models\FactionModel::class)->addRespect((int) $attacker['faction_id'], $playerId, $gain);
                }
            }
        }
        // 'leave' : rien d'autre.

        $db->transComplete();
        return ['ok' => true, 'message' => $msg ?: 'Combat termine, tu pars.'];
    }

    private function resolveUsername(int $playerId): string
    {
        $row = db_connect()->table('players p')
            ->select('users.username')
            ->join('users', 'users.id = p.user_id', 'inner')
            ->where('p.id', $playerId)
            ->get()->getRowArray();
        return (string) ($row['username'] ?? 'inconnu');
    }
}
