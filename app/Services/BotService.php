<?php

namespace App\Services;

use App\Models\CrimeCategoryModel;
use App\Models\CrimeModel;
use App\Models\GameSettingModel;
use App\Models\ItemModel;
use App\Models\PlayerCrimeProgressModel;
use App\Models\PlayerItemModel;
use App\Models\PlayerModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;

/**
 * Bots façon PNJ : des players normaux, indistinguables des humains en front, dont
 * les actions sont declenchees par BotService::tickAll() appele depuis le cron.
 *
 * Chaque bot a une PERSONA qui pondere ses actions. Le service tire une action
 * selon les poids, verifie qu'elle est realisable (etat, ressources), et l'execute
 * via les models existants (donc le bot subit les memes regles qu'un humain : XP,
 * cooldowns, addiction, prison, etc.).
 */
class BotService
{
    /**
     * Poids des actions par persona. Les cles correspondent aux methodes act*().
     * 'idle' = ne fait rien ce tick.
     *
     * Les poids sont relatifs : le service tire avec une roulette ponderee.
     */
    public const PERSONAS = [
        'criminel' => [
            'crime'   => 55,
            'train'   => 10,
            'consume' => 5,
            'buy'     => 5,
            'idle'    => 25,
        ],
        'athlete' => [
            'train'   => 55,
            'crime'   => 10,
            'consume' => 5,
            'buy'     => 5,
            'idle'    => 25,
        ],
        'trafiquant' => [
            'crime'   => 35,
            'consume' => 20,
            'buy'     => 15,
            'train'   => 5,
            'idle'    => 25,
        ],
        'lambda' => [
            'crime'   => 25,
            'train'   => 25,
            'consume' => 10,
            'buy'     => 10,
            'idle'    => 30,
        ],
    ];

    /**
     * Poids des stats a entrainer par persona (sub-pick pour l'action 'train').
     * Les 4 keys correspondent aux slugs de PlayerModel::TRAINABLE_STATS.
     */
    public const STAT_WEIGHTS = [
        'criminel'   => ['reflexes' => 40, 'hack' => 30, 'force' => 20, 'blindage' => 10],
        'athlete'    => ['force'    => 40, 'blindage' => 30, 'reflexes' => 20, 'hack' => 10],
        'trafiquant' => ['hack'     => 40, 'reflexes' => 30, 'force' => 15, 'blindage' => 15],
        'lambda'     => ['force'    => 25, 'blindage' => 25, 'reflexes' => 25, 'hack' => 25],
    ];

    /**
     * Poids des categories de crimes par persona (slug => poids).
     * Les categories inconnues sont juste ignorees, pas de plantage.
     */
    public const CRIME_CAT_WEIGHTS = [
        'criminel'   => ['pickpocket' => 50, 'hack' => 30, 'recherche-cash' => 20],
        'athlete'    => ['recherche-cash' => 60, 'pickpocket' => 30, 'hack' => 10],
        'trafiquant' => ['hack' => 50, 'pickpocket' => 30, 'recherche-cash' => 20],
        'lambda'     => ['recherche-cash' => 40, 'pickpocket' => 30, 'hack' => 30],
    ];

    /** Pool de mots-racines pour les pseudos. Mix cyber, fr et son court. */
    public const PSEUDO_ROOTS = [
        // Cyber / SF
        'Nyx', 'Krash', 'Voltik', 'Zer0', 'Echo', 'Cipher', 'Onyx', 'Glitch', 'Pulse',
        'Drift', 'Volt', 'Cobalt', 'Vex', 'Sable', 'Wraith', 'Static', 'Quartz', 'Hex',
        'Spectre', 'Vortex', 'Mecha', 'Bolt', 'Spike', 'Razor', 'Neon', 'Chrome', 'Wire',
        'Ghost', 'Phantom', 'Reaper', 'Talon', 'Riven', 'Vesper', 'Soren', 'Doll',
        // Plus organique / fr
        'Cendre', 'Bram', 'Spire', 'Knox', 'Mox', 'Slag', 'Wisp', 'Hush', 'Tarn', 'Coyote',
        'Mira', 'Iko', 'Riza', 'Tess', 'Loup', 'Brume', 'Faille', 'Nox', 'Rune', 'Aster',
        'Kael', 'Sven', 'Mael', 'Orso', 'Crow', 'Sting', 'Asher', 'Quill', 'Veck',
        'Vyrk', 'Lupin', 'Ozzy', 'Mira', 'Yuki', 'Kira', 'Renko', 'Jin', 'Tao',
        // Plus brut
        'Bone', 'Cinder', 'Snare', 'Husk', 'Grin', 'Hide', 'Pyre', 'Flint', 'Tusk', 'Crag',
    ];

    /** Prefixes ajoutes occasionnellement pour varier (Dark___, Neo___, Mr___...). */
    public const PSEUDO_PREFIXES = [
        'Dark', 'Neo', 'Cyber', 'Mad', 'Wild', 'Iron', 'Toxic', 'Lone', 'Old', 'Mr',
        'Mrs', 'Sir', 'Dr', 'Captain', 'Lord', 'Lady', 'Cold', 'Saint', 'Lil', 'Big',
    ];

    /** Suffixes-lettres pour les patterns Nom_X, Nom-K, NomZ. */
    public const PSEUDO_SUFFIX_LETTERS = ['x', 'k', 'z', 'v', 'q', 'j', 'r', 's'];

    /**
     * Itere sur tous les bots non-incarceres et fait potentiellement agir chacun
     * selon bot_action_chance_pct. Retourne un compteur des actions executees.
     *
     * @return array{ticked: int, acted: int, by_action: array<string, int>}
     */
    public function tickAll(): array
    {
        $chance = (int) model(GameSettingModel::class)->get('bot_action_chance_pct', 30);
        $bots   = $this->listActiveBots();

        $acted    = 0;
        $byAction = [];

        foreach ($bots as $bot) {
            if (random_int(0, 99) >= $chance) {
                continue;
            }
            $action = $this->runOneAction((int) $bot['id'], (string) $bot['bot_persona']);
            if ($action !== null) {
                $acted++;
                $byAction[$action] = ($byAction[$action] ?? 0) + 1;
            }
        }

        return ['ticked' => count($bots), 'acted' => $acted, 'by_action' => $byAction];
    }

    /**
     * Tire et execute une action pour le bot donne. Renvoie le nom de l'action
     * jouee (success ou non) ou null si rien d'executable.
     */
    public function runOneAction(int $botId, string $persona): ?string
    {
        $playerModel = model(PlayerModel::class);
        $bot = $playerModel->find($botId);
        if ($bot === null || (int) ($bot['is_bot'] ?? 0) !== 1) {
            return null;
        }
        // Skip si prison ou hopital (le tick suivant retentera).
        $now = Time::now();
        if (! empty($bot['in_jail_until']) && Time::parse($bot['in_jail_until'])->isAfter($now)) {
            return null;
        }
        if (! empty($bot['in_hospital_until']) && Time::parse($bot['in_hospital_until'])->isAfter($now)) {
            return null;
        }

        $weights = self::PERSONAS[$persona] ?? self::PERSONAS['lambda'];
        $action  = $this->weightedPick($weights);

        return match ($action) {
            'train'   => $this->actTrain($bot, $persona),
            'crime'   => $this->actCrime($bot, $persona),
            'consume' => $this->actConsume($bot),
            'buy'     => $this->actBuy($bot),
            default   => 'idle',
        };
    }

    /**
     * Cree N bots de la persona donnee. Genere username + email + password aleatoires,
     * insere via Shield UserModel (qui declenche le hook createPlayerOnRegister du
     * App\Models\UserModel), puis update la fiche player avec is_bot/bot_persona.
     *
     * @return array{created: int, errors: array<int, string>}
     */
    public function populate(int $count, string $persona): array
    {
        if (! isset(self::PERSONAS[$persona])) {
            return ['created' => 0, 'errors' => ['Persona inconnue : ' . $persona]];
        }

        $users       = model(UserModel::class);
        $playerModel = model(PlayerModel::class);
        $created     = 0;
        $errors      = [];

        for ($i = 0; $i < $count; $i++) {
            // Genere un pseudo selon un pattern aleatoire pour varier les apparences.
            $username = $this->generatePseudo();
            $email    = strtolower(preg_replace('/[^a-z0-9]/i', '', $username)) . random_int(100, 999) . '@bot.local';

            try {
                $user = new User([
                    'username' => $username,
                    'email'    => $email,
                    'password' => bin2hex(random_bytes(32)),
                ]);
                $users->save($user);
                $userId = $users->getInsertID();
                if (! $userId) {
                    $errors[] = 'Echec insertion user pour ' . $username;
                    continue;
                }

                // Le hook afterInsert a deja cree la ligne players. On la flag.
                $player = $playerModel->where('user_id', $userId)->first();
                if ($player === null) {
                    $errors[] = 'Player non cree pour ' . $username;
                    continue;
                }
                $playerModel->update($player['id'], [
                    'is_bot'      => 1,
                    'bot_persona' => $persona,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = $username . ' : ' . $e->getMessage();
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /** Liste les bots actifs (non en prison/hopital). */
    private function listActiveBots(): array
    {
        return model(PlayerModel::class)
            ->select('id, level, bot_persona, energy_current, nerve_current, hp_current, credits, in_jail_until, in_hospital_until')
            ->where('is_bot', 1)
            ->findAll();
    }

    // ---------- Actions ----------

    private function actTrain(array $bot, string $persona): ?string
    {
        if ((int) $bot['energy_current'] < PlayerModel::TRAIN_ENERGY_COST) {
            return null;
        }
        $weights = self::STAT_WEIGHTS[$persona] ?? self::STAT_WEIGHTS['lambda'];
        $stat    = $this->weightedPick($weights);
        $r = model(PlayerModel::class)->train((int) $bot['id'], $stat);
        return $r['ok'] ? 'train' : null;
    }

    private function actCrime(array $bot, string $persona): ?string
    {
        if ((int) $bot['nerve_current'] < 1) {
            return null;
        }
        // Pick categorie.
        $catWeights = self::CRIME_CAT_WEIGHTS[$persona] ?? self::CRIME_CAT_WEIGHTS['lambda'];
        $catSlug    = $this->weightedPick($catWeights);
        $cat        = model(CrimeCategoryModel::class)->findBySlug($catSlug);
        if ($cat === null) {
            return null;
        }
        // Pick crime debloque le plus dur que possible (pour gain max), fallback au plus facile.
        $progress = model(PlayerCrimeProgressModel::class)->getOrCreate((int) $bot['id'], (int) $cat['id']);
        $crimes   = model(CrimeModel::class)->listForCategory((int) $cat['id']);
        $unlocked = array_filter($crimes, static fn ($c) => (int) $c['min_category_xp'] <= (int) $progress['xp']);
        $affordable = array_filter($unlocked, static fn ($c) => (int) $c['nerve_cost'] <= (int) $bot['nerve_current']);
        if (empty($affordable)) {
            return null;
        }
        // Pick random parmi les abordables (pas forcement le plus dur, evite la specialisation extreme).
        $pick = $affordable[array_rand($affordable)];
        $r = model(CrimeModel::class)->attempt((int) $bot['id'], (int) $pick['id']);
        return $r['ok'] ? 'crime' : null;
    }

    private function actConsume(array $bot): ?string
    {
        // Cherche un consommable dans l'inventaire dont le cooldown est purge.
        $rows = db_connect()->table('player_items pi')
            ->select('pi.id, pi.item_id, pi.quantity, i.consumable_type, i.cooldown_seconds')
            ->join('items i', 'i.id = pi.item_id', 'inner')
            ->where('pi.player_id', (int) $bot['id'])
            ->where('i.consumable_type IS NOT NULL')
            ->where('i.discontinued', 0)
            ->get()->getResultArray();
        if (empty($rows)) {
            return null;
        }
        // Filtre par cooldown via last_*_at sur le bot.
        $now = Time::now();
        $eligible = [];
        foreach ($rows as $r) {
            $lastField = $r['consumable_type'] === 'drug' ? 'last_drug_at' : 'last_booster_at';
            $last = $bot[$lastField] ?? null;
            if (! empty($last) && Time::parse($last)->addSeconds((int) $r['cooldown_seconds'])->isAfter($now)) {
                continue;
            }
            $eligible[] = $r;
        }
        if (empty($eligible)) {
            return null;
        }
        $pick = $eligible[array_rand($eligible)];
        $r = model(PlayerModel::class)->consume((int) $bot['id'], (int) $pick['id']);
        return $r['ok'] ? 'consume' : null;
    }

    private function actBuy(array $bot): ?string
    {
        // Pick item abordable au prix minimal pour eviter de claquer tout le stock.
        $rows = model(ItemModel::class)
            ->where('discontinued', 0)
            ->where('price >', 0)
            ->where('price <=', (int) $bot['credits'])
            ->orderBy('price')
            ->findAll();
        if (empty($rows)) {
            return null;
        }
        // Limite aux 5 moins chers pour rester realiste.
        $cheap = array_slice($rows, 0, 5);
        $pick  = $cheap[array_rand($cheap)];

        $db = db_connect();
        $db->transStart();
        model(PlayerModel::class)->builder()
            ->where('id', (int) $bot['id'])
            ->where('credits >=', (int) $pick['price'])
            ->update([
                'credits'    => new \CodeIgniter\Database\RawSql('credits - ' . (int) $pick['price']),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return null;
        }
        model(PlayerItemModel::class)->insert([
            'player_id' => (int) $bot['id'],
            'item_id'   => (int) $pick['id'],
            'equipped'  => 0,
            'quantity'  => 1,
        ]);
        $db->transComplete();
        return 'buy';
    }

    /**
     * Genere un pseudo plausible en variant les patterns pour eviter le "Nom + Chiffres" repetitif.
     * 8 patterns possibles, ponderes pour favoriser les pseudos courts mais en glisser quelques baroques.
     */
    private function generatePseudo(): string
    {
        $root = self::PSEUDO_ROOTS[array_rand(self::PSEUDO_ROOTS)];

        $patterns = [
            'plain'        => 30,  // Nyx
            'plain_lower'  => 8,   // nyx
            'plain_upper'  => 4,   // NYX
            'num_attached' => 12,  // Nyx42, Nyx7
            'num_dashed'   => 8,   // Nyx-42
            'letter_dot'   => 6,   // Nyx.x
            'letter_under' => 6,   // Nyx_x
            'prefixed'     => 12,  // DarkNyx, OldNyx
            'double'       => 8,   // NyxBolt, Nyx-Bolt
            'leet'         => 6,   // Nyx_404, z3r0
        ];
        $pattern = $this->weightedPick($patterns);

        return match ($pattern) {
            'plain'        => $root,
            'plain_lower'  => strtolower($root),
            'plain_upper'  => strtoupper($root),
            'num_attached' => $root . random_int(1, 99),
            'num_dashed'   => $root . '-' . random_int(2, 99),
            'letter_dot'   => $root . '.' . self::PSEUDO_SUFFIX_LETTERS[array_rand(self::PSEUDO_SUFFIX_LETTERS)],
            'letter_under' => $root . '_' . self::PSEUDO_SUFFIX_LETTERS[array_rand(self::PSEUDO_SUFFIX_LETTERS)],
            'prefixed'     => self::PSEUDO_PREFIXES[array_rand(self::PSEUDO_PREFIXES)] . $root,
            'double'       => $root . (random_int(0, 1) ? '-' : '') . self::PSEUDO_ROOTS[array_rand(self::PSEUDO_ROOTS)],
            'leet'         => strtolower($root) . '_' . random_int(100, 999),
            default        => $root,
        };
    }

    /**
     * Roulette ponderee : pioche une cle d'apres ses poids.
     *
     * @param array<string, int> $weights
     */
    private function weightedPick(array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return (string) array_key_first($weights);
        }
        $roll = random_int(1, $total);
        $cum  = 0;
        foreach ($weights as $key => $w) {
            $cum += (int) $w;
            if ($roll <= $cum) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }
}
