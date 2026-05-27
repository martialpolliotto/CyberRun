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
        'in_jail_until',
        'addiction_level',
        'addiction_updated_at',
        'last_booster_at',
        'last_drug_at',
    ];

    /** Couts et probas de l'evasion solo depuis la prison. */
    public const ESCAPE_NERVE_COST           = 10;
    public const ESCAPE_BASE_PCT             = 20;
    public const ESCAPE_MAX_PCT              = 75;
    public const ESCAPE_FAIL_PENALTY_MINUTES = 5;

    /** Decay journalier du seuil de dependance (points par jour ecoule depuis le dernier check). */
    public const ADDICTION_DAILY_DECAY = 10;

    /**
     * Paliers de dependance et leurs effets negatifs. Tries du plus eleve au plus bas.
     *
     * - stat_malus      : valeur soustraite a chaque stat effective (Force/Blindage/Reflexes/Hack)
     * - overdose_bonus  : pourcentage de points ajoutes au overdose_chance_pct du roll drug
     *
     * @var array<int, array{min: int, label: string, stat_malus: int, overdose_bonus: int}>
     */
    public const ADDICTION_TIERS = [
        ['min' => 100, 'label' => 'sevrage',   'stat_malus' => 10, 'overdose_bonus' => 20],
        ['min' => 75,  'label' => 'dépendant', 'stat_malus' => 5,  'overdose_bonus' => 10],
        ['min' => 50,  'label' => 'accro',     'stat_malus' => 2,  'overdose_bonus' => 5],
        ['min' => 25,  'label' => 'éveillé',   'stat_malus' => 0,  'overdose_bonus' => 0],
        ['min' => 0,   'label' => 'clean',     'stat_malus' => 0,  'overdose_bonus' => 0],
    ];

    /**
     * Retourne le tier d'addiction qui s'applique au level donne.
     *
     * @return array{min: int, label: string, stat_malus: int, overdose_bonus: int}
     */
    public static function addictionTier(int $level): array
    {
        foreach (self::ADDICTION_TIERS as $tier) {
            if ($level >= $tier['min']) {
                return $tier;
            }
        }
        return self::ADDICTION_TIERS[count(self::ADDICTION_TIERS) - 1];
    }

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
     * Calcule les stats effectives (base + bonus des items équipés + bonus des effets actifs).
     *
     * @return array{
     *   base: array{force:int,blindage:int,reflexes:int,hack:int},
     *   bonus: array{force:int,blindage:int,reflexes:int,hack:int},
     *   active: array{force:int,blindage:int,reflexes:int,hack:int},
     *   total: array{force:int,blindage:int,reflexes:int,hack:int}
     * }
     */
    public function getEffectiveStats(int $playerId): array
    {
        $player = $this->find($playerId);
        if ($player === null) {
            $zero = ['force' => 0, 'blindage' => 0, 'reflexes' => 0, 'hack' => 0];
            return ['base' => $zero, 'bonus' => $zero, 'active' => $zero, 'total' => $zero];
        }

        $base = [
            'force'    => (int) $player['stat_force'],
            'blindage' => (int) $player['stat_blindage'],
            'reflexes' => (int) $player['stat_reflexes'],
            'hack'     => (int) $player['stat_hack'],
        ];

        // NB: "force" est un mot réservé MariaDB → on utilise des alias prefixés.
        $row = $this->db->query(
            'SELECT
                COALESCE(SUM(i.bonus_force), 0)    AS bf,
                COALESCE(SUM(i.bonus_blindage), 0) AS bb,
                COALESCE(SUM(i.bonus_reflexes), 0) AS br,
                COALESCE(SUM(i.bonus_hack), 0)     AS bh
             FROM player_items pi
             JOIN items i ON i.id = pi.item_id
             WHERE pi.player_id = ? AND pi.equipped = 1',
            [$playerId],
        )->getRow();

        $bonus = [
            'force'    => (int) ($row->bf ?? 0),
            'blindage' => (int) ($row->bb ?? 0),
            'reflexes' => (int) ($row->br ?? 0),
            'hack'     => (int) ($row->bh ?? 0),
        ];

        // Bonus temporaires depuis les effets actifs (drogues + boosters).
        $effects = model(PlayerActiveEffectModel::class)->aggregateBonuses($playerId);
        $active = [
            'force'    => $effects['force'],
            'blindage' => $effects['blindage'],
            'reflexes' => $effects['reflexes'],
            'hack'     => $effects['hack'],
        ];

        // Malus du tier d'addiction (sevrage, dependant...).
        $tier  = self::addictionTier((int) ($player['addiction_level'] ?? 0));
        $malus = (int) $tier['stat_malus'];
        $addiction = [
            'force'    => $malus,
            'blindage' => $malus,
            'reflexes' => $malus,
            'hack'     => $malus,
        ];

        $total = [];
        foreach ($base as $k => $v) {
            $total[$k] = max(0, $v + $bonus[$k] + $active[$k] - $addiction[$k]);
        }

        return ['base' => $base, 'bonus' => $bonus, 'active' => $active, 'addiction' => $addiction, 'total' => $total];
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

    /**
     * Donne $amount XP au player. Si le total franchit le seuil level*100, level-up
     * en cascade tant que le seuil est atteint (XP reportee sur le niveau suivant).
     */
    public function grantXp(int $playerId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        $player = $this->find($playerId);
        if ($player === null) {
            return;
        }

        $level = (int) $player['level'];
        $xp    = (int) $player['xp'] + $amount;

        while ($xp >= $level * 100) {
            $xp -= $level * 100;
            $level++;
        }

        $this->update($playerId, [
            'level' => $level,
            'xp'    => $xp,
        ]);
    }

    /**
     * Tentative d'evasion depuis la prison. Coute ESCAPE_NERVE_COST nerve.
     * Roll : ESCAPE_BASE_PCT + reflexes / 2, cape a ESCAPE_MAX_PCT.
     * - Succes : in_jail_until vide a maintenant.
     * - Echec : ESCAPE_FAIL_PENALTY_MINUTES ajoutees a la peine.
     *
     * @return array{ok: bool, message: string, escaped?: bool, success_pct?: int}
     */
    public function attemptEscape(int $playerId): array
    {
        $player = $this->find($playerId);
        if ($player === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }

        $now = Time::now();
        if (empty($player['in_jail_until']) || Time::parse($player['in_jail_until'])->isBefore($now)) {
            return ['ok' => false, 'message' => 'Tu n\'es pas en prison.'];
        }

        if ((int) $player['nerve_current'] < self::ESCAPE_NERVE_COST) {
            return ['ok' => false, 'message' => 'Nerve insuffisante (' . self::ESCAPE_NERVE_COST . ' requise).'];
        }

        // Debit nerve atomique.
        $affected = $this->builder()
            ->where('id', $playerId)
            ->where('nerve_current >=', self::ESCAPE_NERVE_COST)
            ->update([
                'nerve_current' => new \CodeIgniter\Database\RawSql('nerve_current - ' . self::ESCAPE_NERVE_COST),
                'updated_at'    => $now->toDateTimeString(),
            ]);
        if (! $affected) {
            return ['ok' => false, 'message' => 'Nerve insuffisante.'];
        }

        $successPct = min(self::ESCAPE_MAX_PCT, self::ESCAPE_BASE_PCT + (int) $player['stat_reflexes'] / 2);
        $roll       = random_int(0, 99);

        if ($roll < $successPct) {
            $this->update($playerId, ['in_jail_until' => null]);
            return [
                'ok'          => true,
                'message'     => 'Evasion reussie ! Tu file dans les egouts, libre.',
                'escaped'     => true,
                'success_pct' => (int) $successPct,
            ];
        }

        $newUntil = Time::parse($player['in_jail_until'])->addMinutes(self::ESCAPE_FAIL_PENALTY_MINUTES)->toDateTimeString();
        $this->update($playerId, ['in_jail_until' => $newUntil]);

        return [
            'ok'          => true,
            'message'     => 'Tentative ratee. Les matons t\'ont rattrape, +' . self::ESCAPE_FAIL_PENALTY_MINUTES . ' minutes au compteur.',
            'escaped'     => false,
            'success_pct' => (int) $successPct,
        ];
    }

    /**
     * Decay lazy de l'addiction : on calcule combien de jours se sont ecoules depuis le
     * dernier check, on retire ADDICTION_DAILY_DECAY x jours, et on met a jour l'horodatage.
     *
     * Appele systematiquement avant toute modification d'addiction (consume notamment).
     */
    public function decayAddiction(int $playerId): void
    {
        $player = $this->find($playerId);
        if ($player === null) {
            return;
        }
        $now = Time::now();
        if (empty($player['addiction_updated_at'])) {
            // Init silencieux, pas de decay au premier passage.
            $this->update($playerId, ['addiction_updated_at' => $now->toDateTimeString()]);
            return;
        }
        $elapsed = $now->getTimestamp() - Time::parse($player['addiction_updated_at'])->getTimestamp();
        $days    = intdiv($elapsed, 86400);
        if ($days <= 0) {
            return; // Pas encore un jour entier ecoule.
        }
        $newLevel = max(0, (int) $player['addiction_level'] - $days * self::ADDICTION_DAILY_DECAY);
        // On avance l'horodatage du nombre exact de jours decayes (le reste s'accumule).
        $newUpdated = Time::parse($player['addiction_updated_at'])->addDays($days)->toDateTimeString();
        $this->update($playerId, [
            'addiction_level'      => $newLevel,
            'addiction_updated_at' => $newUpdated,
        ]);
    }

    /**
     * Consomme un item de l'inventaire du joueur. Applique :
     *  - cooldown (par kind : booster | drug)
     *  - empechement si effet du meme kind deja actif
     *  - decay addiction (lazy)
     *  - roll overdose pour drogue
     *  - regen instantanee HP/NRG/NRV (capee aux max courants)
     *  - effet temporaire (insert/update player_active_effects) si duration > 0
     *  - increment addiction pour drogue
     *  - decrement de l'item dans l'inventaire (quantity-- ou suppression)
     *
     * @return array{ok: bool, message: string, outcome?: string}
     */
    public function consume(int $playerId, int $playerItemId): array
    {
        $now = Time::now();

        // Recupere item joint a player_item pour avoir les effets + ownership.
        $row = $this->db->table('player_items pi')
            ->select('pi.*, i.id AS item_id, i.name AS item_name, i.consumable_type, i.cooldown_seconds,
                      i.effect_hp, i.effect_nrg, i.effect_nrv,
                      i.effect_force, i.effect_blindage, i.effect_reflexes, i.effect_hack,
                      i.effect_hp_max, i.effect_nrg_max, i.effect_nrv_max,
                      i.effect_duration_seconds,
                      i.addiction_threshold_increase,
                      i.overdose_chance_pct, i.overdose_hospital_min, i.overdose_hospital_max,
                      i.discontinued')
            ->join('items i', 'i.id = pi.item_id', 'inner')
            ->where('pi.id', $playerItemId)
            ->where('pi.player_id', $playerId)
            ->get()->getRowArray();

        if ($row === null) {
            return ['ok' => false, 'message' => 'Item introuvable dans ton inventaire.'];
        }
        if ((int) $row['discontinued'] === 1) {
            return ['ok' => false, 'message' => 'Item hors-circuit, impossible a consommer.'];
        }
        $kind = $row['consumable_type'] ?? null;
        if (! in_array($kind, ItemModel::CONSUMABLE_TYPES, true)) {
            return ['ok' => false, 'message' => 'Cet item n\'est pas consommable.'];
        }

        // Empechement : ne pas consommer en prison ni a l'hopital.
        $player = $this->find($playerId);
        if (! empty($player['in_hospital_until']) && Time::parse($player['in_hospital_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es a la cyberclinique, impossible de consommer quoi que ce soit.'];
        }
        if (! empty($player['in_jail_until']) && Time::parse($player['in_jail_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es en prison, on t\'a fouille, rien sur toi.'];
        }

        // Cooldown par kind.
        $lastField = $kind === 'drug' ? 'last_drug_at' : 'last_booster_at';
        if (! empty($player[$lastField])) {
            $nextAllowed = Time::parse($player[$lastField])->addSeconds((int) $row['cooldown_seconds']);
            if ($nextAllowed->isAfter($now)) {
                $remaining = $nextAllowed->getTimestamp() - $now->getTimestamp();
                $mins = (int) ceil($remaining / 60);
                return ['ok' => false, 'message' => 'Cooldown ' . esc($kind) . ' : encore ' . $mins . ' min a attendre.'];
            }
        }

        // Verifie qu'aucun effet du meme kind n'est deja actif.
        $effectsModel = model(PlayerActiveEffectModel::class);
        if ($effectsModel->hasActive($playerId, $kind)) {
            return ['ok' => false, 'message' => 'Tu es deja sous l\'effet d\'un ' . esc($kind) . '. Attends qu\'il termine.'];
        }

        // Decay lazy avant toute modif d'addiction.
        $this->decayAddiction($playerId);
        $player = $this->find($playerId);

        $db = db_connect();
        $db->transStart();

        // ---- Roll overdose pour les drogues ----
        // L'addiction ajoute un bonus % au roll (sevrage = +20%, dependant = +10%, etc.).
        $addictionTier      = self::addictionTier((int) $player['addiction_level']);
        $effectiveOverdose  = (int) $row['overdose_chance_pct'] + (int) $addictionTier['overdose_bonus'];
        if ($kind === 'drug' && $effectiveOverdose > 0) {
            if (random_int(0, 99) < $effectiveOverdose) {
                $minM = (int) $row['overdose_hospital_min'];
                $maxM = max($minM, (int) $row['overdose_hospital_max']);
                $minutes = random_int($minM, $maxM);
                $until   = $now->addMinutes($minutes)->toDateTimeString();

                $this->update($playerId, [
                    'in_hospital_until' => $until,
                    $lastField          => $now->toDateTimeString(),
                    'addiction_level'   => (int) $player['addiction_level'] + (int) $row['addiction_threshold_increase'],
                ]);
                // L'item est tout de meme consomme.
                $this->consumeOneFromInventory($playerItemId, (int) $row['quantity']);

                $db->transComplete();
                return [
                    'ok'      => true,
                    'message' => 'OVERDOSE. Cyberclinique pour ' . $minutes . ' minutes.',
                    'outcome' => 'overdose',
                ];
            }
        }

        // ---- Regen instantanee ----
        $newHp  = min((int) $player['hp_max'],     (int) $player['hp_current']     + (int) $row['effect_hp']);
        $newNrg = min((int) $player['energy_max'], (int) $player['energy_current'] + (int) $row['effect_nrg']);
        $newNrv = min((int) $player['nerve_max'],  (int) $player['nerve_current']  + (int) $row['effect_nrv']);

        $updates = [
            'hp_current'     => $newHp,
            'energy_current' => $newNrg,
            'nerve_current'  => $newNrv,
            $lastField       => $now->toDateTimeString(),
        ];
        if ($kind === 'drug' && (int) $row['addiction_threshold_increase'] > 0) {
            $updates['addiction_level'] = (int) $player['addiction_level'] + (int) $row['addiction_threshold_increase'];
        }
        $this->update($playerId, $updates);

        // ---- Effet temporaire (stat ou stat max) ----
        $hasTemporary = (int) $row['effect_duration_seconds'] > 0 && (
            $row['effect_force'] || $row['effect_blindage'] || $row['effect_reflexes'] || $row['effect_hack']
            || $row['effect_hp_max'] || $row['effect_nrg_max'] || $row['effect_nrv_max']
        );
        if ($hasTemporary) {
            $expiresAt = $now->addSeconds((int) $row['effect_duration_seconds']);
            $effectsModel->setOrReplace($playerId, $kind, (int) $row['item_id'], $expiresAt);
        }

        // ---- Decrement de l'inventaire ----
        $this->consumeOneFromInventory($playerItemId, (int) $row['quantity']);

        $db->transComplete();

        $bits = [];
        if ((int) $row['effect_hp']  > 0) $bits[] = '+' . (int) $row['effect_hp']  . ' HP';
        if ((int) $row['effect_nrg'] > 0) $bits[] = '+' . (int) $row['effect_nrg'] . ' NRG';
        if ((int) $row['effect_nrv'] > 0) $bits[] = '+' . (int) $row['effect_nrv'] . ' NRV';
        if ($hasTemporary) $bits[] = 'effet temporaire actif';

        return [
            'ok'      => true,
            'message' => esc($row['item_name']) . ' consomme.' . ($bits === [] ? '' : ' (' . implode(', ', $bits) . ')'),
            'outcome' => 'consumed',
        ];
    }

    /** Decremente la quantite d'un player_item de 1, supprime la ligne si on tombe a 0. */
    private function consumeOneFromInventory(int $playerItemId, int $currentQuantity): void
    {
        $piModel = model(PlayerItemModel::class);
        if ($currentQuantity <= 1) {
            $piModel->delete($playerItemId);
        } else {
            $piModel->update($playerItemId, ['quantity' => $currentQuantity - 1]);
        }
    }
}
