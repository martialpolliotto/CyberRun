<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class CrimeModel extends Model
{
    protected $table         = 'crimes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'category_id', 'slug', 'name', 'description',
        'nerve_cost', 'min_category_xp',
        'base_success_pct', 'critical_fail_pct',
        'reward_credits_min', 'reward_credits_max', 'reward_xp', 'reward_category_xp',
        'critical_destination', 'critical_minutes_min', 'critical_minutes_max',
        'time_bonus_pct', 'time_bonus_hour_start', 'time_bonus_hour_end',
    ];

    public const CRITICAL_DESTINATIONS = ['jail', 'hospital'];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Tous les crimes d'une categorie, ordonnes par seuil croissant. */
    public function listForCategory(int $categoryId): array
    {
        return $this->where('category_id', $categoryId)
            ->orderBy('min_category_xp')
            ->orderBy('nerve_cost')
            ->findAll();
    }

    /**
     * Indique si un crime est debloque pour ce joueur (XP categorie suffisante).
     * @param array<string,mixed> $crime
     */
    public function isUnlockedFor(array $crime, int $playerCategoryXp): bool
    {
        return $playerCategoryXp >= (int) $crime['min_category_xp'];
    }

    /**
     * Calcule le taux de reussite estime pour ce crime, ce joueur, a cet instant.
     * Renvoie un pourcentage entier 0..95.
     *
     * Facteurs : base + stat_effective/2 + category_xp/10 + time_bonus (si fenetre active).
     *
     * La stat dominante est lue depuis $effectiveTotals (qui contient deja les bonus
     * d'equipement, effets actifs et le malus du tier d'addiction). Si non fourni,
     * fallback a la stat de base brute du player (pour les appels legacy).
     *
     * @param array<string,mixed> $crime
     * @param array<string,mixed> $category
     * @param array<string,mixed> $player
     * @param array{force:int,blindage:int,reflexes:int,hack:int}|null $effectiveTotals
     */
    public function estimateSuccessPct(array $crime, array $category, array $player, int $playerCategoryXp, ?array $effectiveTotals = null, ?int $hourOverride = null): int
    {
        $bonus = 0.0;

        // Stat dominante (si la categorie en a une).
        $statSlug = $category['primary_stat'] ?? null;
        if ($statSlug !== null && isset(PlayerModel::TRAINABLE_STATS[$statSlug])) {
            if ($effectiveTotals !== null && isset($effectiveTotals[$statSlug])) {
                $statValue = (int) $effectiveTotals[$statSlug];
            } else {
                $col = PlayerModel::TRAINABLE_STATS[$statSlug];
                $statValue = (int) ($player[$col] ?? 0);
            }
            $bonus += $statValue / 2.0;
        }

        // XP de specialisation dans la categorie.
        $bonus += $playerCategoryXp / 10.0;

        // Bonus horaire.
        if ($this->isTimeBonusActive($crime, $hourOverride)) {
            $bonus += (int) $crime['time_bonus_pct'];
        }

        $pct = (int) round((int) $crime['base_success_pct'] + $bonus);
        return max(0, min(95, $pct));
    }

    /**
     * Tente l'execution d'un crime pour un joueur. Atomicite des conditions critiques :
     * - check nerve / debit nerve
     * - check prison/hopital
     * - check XP categorie
     * Puis roll : critical -> success -> sinon echec simple.
     * Applique recompenses ou consequences en transaction.
     *
     * @return array{
     *   ok: bool,
     *   message: string,
     *   outcome?: 'success'|'fail'|'critical',
     *   credits_gained?: int,
     *   xp_gained?: int,
     *   category_xp_gained?: int,
     *   critical_destination?: string,
     *   critical_minutes?: int,
     *   success_pct?: int,
     *   critical_pct?: int
     * }
     */
    public function attempt(int $playerId, int $crimeId): array
    {
        $crime = $this->find($crimeId);
        if ($crime === null) {
            return ['ok' => false, 'message' => 'Crime introuvable.'];
        }

        $category = model(CrimeCategoryModel::class)->find((int) $crime['category_id']);
        if ($category === null) {
            return ['ok' => false, 'message' => 'Categorie introuvable.'];
        }

        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->find($playerId);
        if ($player === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }

        // Etats bloquants.
        $now = Time::now();
        if (! empty($player['in_hospital_until']) && Time::parse($player['in_hospital_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es a la cyberclinique, impossible de tenter un crime.'];
        }
        if (! empty($player['in_jail_until']) && Time::parse($player['in_jail_until'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Tu es en prison, impossible de tenter un crime.'];
        }

        // Verification XP categorie.
        $progress = model(PlayerCrimeProgressModel::class)->getOrCreate($playerId, (int) $category['id']);
        if ((int) $progress['xp'] < (int) $crime['min_category_xp']) {
            return ['ok' => false, 'message' => 'Tu n\'as pas assez d\'experience dans cette categorie pour tenter ce crime.'];
        }

        // Debit nerve atomique : refuse si entre-temps la nerve est passee en dessous.
        $nerveCost = (int) $crime['nerve_cost'];
        if ((int) $player['nerve_current'] < $nerveCost) {
            return ['ok' => false, 'message' => 'Nerve insuffisante (' . $nerveCost . ' requise).'];
        }

        $db = db_connect();
        $db->transStart();

        $playerModel->builder()
            ->where('id', $playerId)
            ->where('nerve_current >=', $nerveCost)
            ->update([
                'nerve_current' => new RawSql('nerve_current - ' . $nerveCost),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Nerve insuffisante au moment de la tentative.'];
        }

        // Increment attempts (progress).
        model(PlayerCrimeProgressModel::class)->update($progress['id'], [
            'attempts' => new RawSql('attempts + 1'),
        ]);

        // ---- Resolution du roll ----
        // Stats effectives pour que les bonus actifs (drogues) et le malus addiction comptent.
        $stats       = $playerModel->getEffectiveStats((int) $player['id']);
        $successPct  = $this->estimateSuccessPct($crime, $category, $player, (int) $progress['xp'], $stats['total']);
        $criticalPct = (int) $crime['critical_fail_pct'];

        $rollCritical = random_int(0, 99);
        $rollSuccess  = random_int(0, 99);

        if ($rollCritical < $criticalPct) {
            // ---- Echec critique : hopital ou prison ----
            // On pioche d'abord la variante, qui peut override dest / minutes.
            $variant = model(CrimeTextModel::class)->pickRandom((int) $crime['id'], 'critical');

            $dest = $variant['critical_destination'] ?? $crime['critical_destination'];
            $dest = in_array((string) $dest, self::CRITICAL_DESTINATIONS, true) ? (string) $dest : (string) $crime['critical_destination'];

            $minMin = $variant['critical_minutes_min'] ?? $crime['critical_minutes_min'];
            $minMax = $variant['critical_minutes_max'] ?? $crime['critical_minutes_max'];
            $minutes = random_int((int) $minMin, max((int) $minMin, (int) $minMax));
            $until   = $now->addMinutes($minutes)->toDateTimeString();

            $updateField = $dest === 'hospital' ? 'in_hospital_until' : 'in_jail_until';
            $playerModel->update($playerId, [$updateField => $until]);

            $db->transComplete();

            $narrative = $variant['text'] ?? 'Echec critique.';
            $suffix    = $dest === 'hospital'
                ? "\n\n→ Cyberclinique pour " . $minutes . ' minutes.'
                : "\n\n→ Coffre pour " . $minutes . ' minutes.';

            \App\Services\ActivityLogger::log($playerId, 'crime', 'Log.crime_critical', [
                'crime_name'        => $crime['name'],
                'destination_label' => lang('Log.destination_' . $dest),
                'minutes'           => $minutes,
            ], null, (int) $crime['id']);

            return [
                'ok'                   => true,
                'message'              => $narrative . $suffix,
                'outcome'              => 'critical',
                'critical_destination' => $dest,
                'critical_minutes'     => $minutes,
                'success_pct'          => $successPct,
                'critical_pct'         => $criticalPct,
            ];
        }

        if ($rollSuccess < $successPct) {
            // ---- Reussite ----
            // On pioche d'abord la variante, qui peut override credits / xp.
            $variant = model(CrimeTextModel::class)->pickRandom((int) $crime['id'], 'success');

            $minC = $variant['reward_credits_min'] ?? $crime['reward_credits_min'];
            $maxC = $variant['reward_credits_max'] ?? $crime['reward_credits_max'];
            $credits = random_int((int) $minC, max((int) $minC, (int) $maxC));
            $xp      = (int) ($variant['reward_xp']          ?? $crime['reward_xp']);
            $catXp   = (int) ($variant['reward_category_xp'] ?? $crime['reward_category_xp']);

            $playerModel->creditUnconditional($playerId, $credits);
            if ($xp > 0) {
                $playerModel->grantXp($playerId, $xp);
            }
            if ($catXp > 0) {
                model(PlayerCrimeProgressModel::class)->update($progress['id'], [
                    'xp'        => new RawSql('xp + ' . $catXp),
                    'successes' => new RawSql('successes + 1'),
                ]);
            } else {
                model(PlayerCrimeProgressModel::class)->update($progress['id'], [
                    'successes' => new RawSql('successes + 1'),
                ]);
            }

            // Hooks missions + faction respect AVANT transComplete : tous les writes
            // sont commit ou roll back ensemble (atomicite). Avant ce changement, un
            // throw dans trackEvent laissait credits/xp donnes mais missions perdues.
            $missionModel = model(MissionModel::class);
            $missionModel->trackEvent($playerId, 'commit_crime', (string) $crime['slug']);
            $missionModel->trackEvent($playerId, 'commit_crime', (string) $category['slug']);
            $missionModel->recheckThresholdsForPlayer($playerId);

            if (! empty($player['faction_id'])) {
                $respectGain = (int) model(GameSettingModel::class)->get('faction_respect_per_crime', 1);
                if ($respectGain > 0) {
                    model(FactionModel::class)->addRespect((int) $player['faction_id'], $playerId, $respectGain);
                }
            }

            $db->transComplete();

            $narrative = $variant['text'] ?? 'Reussite.';
            $suffix    = "\n\n→ +" . $credits . ' credits · +' . $xp . ' XP · +' . $catXp . ' XP ' . $category['name'];

            \App\Services\ActivityLogger::log($playerId, 'crime', 'Log.crime_success', [
                'crime_name' => $crime['name'],
                'credits'    => $credits,
                'xp'         => $xp,
                'cat_xp'     => $catXp,
                'cat_name'   => $category['name'],
            ], null, (int) $crime['id']);

            return [
                'ok'                  => true,
                'message'             => $narrative . $suffix,
                'outcome'             => 'success',
                'credits_gained'      => $credits,
                'xp_gained'           => $xp,
                'category_xp_gained'  => $catXp,
                'success_pct'         => $successPct,
                'critical_pct'        => $criticalPct,
            ];
        }

        // ---- Echec simple : nerve depensee, rien d'autre ----
        $db->transComplete();

        $variant   = model(CrimeTextModel::class)->pickRandom((int) $crime['id'], 'fail');
        $narrative = $variant['text'] ?? 'Echec : la tentative a foire, tu rentres bredouille.';

        \App\Services\ActivityLogger::log($playerId, 'crime', 'Log.crime_fail', [
            'crime_name' => $crime['name'],
        ], null, (int) $crime['id']);

        return [
            'ok'           => true,
            'message'      => $narrative,
            'outcome'      => 'fail',
            'success_pct'  => $successPct,
            'critical_pct' => $criticalPct,
        ];
    }

    /**
     * Le crime a-t-il un bonus horaire actif a l'heure donnee (defaut = maintenant) ?
     * Gere la fenetre qui wrap minuit (ex: 22h-5h).
     *
     * @param array<string,mixed> $crime
     */
    public function isTimeBonusActive(array $crime, ?int $hourOverride = null): bool
    {
        $start = $crime['time_bonus_hour_start'];
        $end   = $crime['time_bonus_hour_end'];
        if ($start === null || $end === null || (int) $crime['time_bonus_pct'] <= 0) {
            return false;
        }
        $h = $hourOverride ?? (int) Time::now()->getHour();
        $start = (int) $start;
        $end   = (int) $end;

        if ($start <= $end) {
            return $h >= $start && $h < $end;
        }
        // wraps midnight (ex: 22-5)
        return $h >= $start || $h < $end;
    }
}
