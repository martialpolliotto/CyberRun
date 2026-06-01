<?php

namespace App\Services;

use App\Models\ChatMessageModel;
use App\Models\GameSettingModel;
use App\Models\PlayerModel;
use CodeIgniter\I18n\Time;

/**
 * Logique chat : validation, antiflood, censure, blocage liens, mute global.
 * Le rendu HTML (escape + parsing @user) reste cote view, jamais ici.
 */
class ChatService
{
    /** Channels valides : 'global', 'newbie', 'trade', 'company', 'faction-{id}'. */
    public const CHANNEL_GLOBAL  = 'global';
    public const CHANNEL_NEWBIE  = 'newbie';
    public const CHANNEL_TRADE   = 'trade';
    public const CHANNEL_COMPANY = 'company';

    /**
     * Registry centralise des channels OUVERTS A TOUS (key => label).
     * Source unique : ajouter une row ici suffit pour que le channel devienne accessible
     * en lecture, en ecriture et apparaisse dans les tabs du widget.
     * Les channels scopes (faction-{id}, company-{id}) sont gerés dynamiquement.
     */
    public const PUBLIC_CHANNELS = [
        self::CHANNEL_GLOBAL  => 'Global',
        self::CHANNEL_TRADE   => 'Trade',
        self::CHANNEL_NEWBIE  => 'Débutants',
        self::CHANNEL_COMPANY => 'Company',
    ];

    /**
     * @return array{ok: bool, message: string, message_id?: int}
     */
    public function send(int $playerId, string $channel, string $rawBody): array
    {
        $settings = model(GameSettingModel::class);
        $body = trim($rawBody);

        if ($body === '') {
            return ['ok' => false, 'message' => 'Message vide.'];
        }
        $maxLen = (int) $settings->get('chat_msg_max_len', 500);
        if (mb_strlen($body) > $maxLen) {
            return ['ok' => false, 'message' => 'Message trop long (max ' . $maxLen . ').'];
        }

        // Validation channel + permission.
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->find($playerId);
        if ($player === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }
        if (! $this->canPostToChannel($player, $channel)) {
            return ['ok' => false, 'message' => 'Channel inaccessible.'];
        }

        // Mute global.
        if (! empty($player['chat_muted_until']) && Time::parse($player['chat_muted_until'])->isAfter(Time::now())) {
            return ['ok' => false, 'message' => 'Tu es mute jusqu\'a ' . esc($player['chat_muted_until']) . '.'];
        }

        // Liens externes.
        if ((bool) $settings->get('chat_block_external_links', true)) {
            if (preg_match('#https?://|www\.#i', $body) === 1) {
                return ['ok' => false, 'message' => 'Liens externes interdits dans le chat.'];
            }
        }

        // Antiflood : 3 couches, serialisees par joueur via SELECT FOR UPDATE.
        // Sans le lock, des requetes paralleles passent toutes le count=0 et inserent
        // toutes (le check + l'insert ne sont pas atomiques en bdd).
        $msgModel = model(ChatMessageModel::class);
        $db       = db_connect();

        $db->transStart();

        // Lock per-player : serialize les sends concurrents pour ce joueur jusqu'au commit.
        $db->query('SELECT id FROM players WHERE id = ? FOR UPDATE', [$playerId]);

        $hard       = (int) $settings->get('chat_rate_hard_seconds', 2);
        $burstCount = (int) $settings->get('chat_rate_burst_count', 5);
        $burstSecs  = (int) $settings->get('chat_rate_burst_seconds', 10);
        $softCount  = (int) $settings->get('chat_rate_soft_count', 10);
        $softSecs   = (int) $settings->get('chat_rate_soft_seconds', 60);

        // 1 seule requete pour les 3 fenetres glissantes (au lieu de 3 COUNT separes).
        $counts = $msgModel->recentSendCountsMulti(
            $playerId,
            max(1, $hard),
            max(1, $burstSecs),
            max(1, $softSecs),
        );

        if ($hard > 0 && $counts['hard'] > 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Tu envoies trop vite. Attends ' . $hard . 's.'];
        }
        if ($burstCount > 0 && $burstSecs > 0 && $counts['burst'] >= $burstCount) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Trop de messages en rafale. Attends un peu.'];
        }
        if ($softCount > 0 && $softSecs > 0 && $counts['soft'] >= $softCount) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Limite de ' . $softCount . ' messages/min atteinte.'];
        }

        // Censure (replace transparent).
        $body = $this->censor($body, (string) $settings->get('chat_blacklist_csv', ''));

        $msgModel->insert([
            'channel'          => $channel,
            'sender_player_id' => $playerId,
            'body'             => $body,
        ]);
        $messageId = (int) $msgModel->getInsertID();

        $db->transComplete();

        return ['ok' => true, 'message' => 'OK', 'message_id' => $messageId];
    }

    /** Verifie qu'un joueur peut poster sur ce channel. */
    public function canPostToChannel(array $player, string $channel): bool
    {
        if (isset(self::PUBLIC_CHANNELS[$channel])) {
            return true;
        }
        if (preg_match('/^faction-(\d+)$/', $channel, $m) === 1) {
            return ! empty($player['faction_id']) && (int) $player['faction_id'] === (int) $m[1];
        }
        return false;
    }

    /** Channels accessibles a un joueur (pour lecture). */
    public function visibleChannels(array $player): array
    {
        $out = [];
        foreach (self::PUBLIC_CHANNELS as $key => $label) {
            $out[] = ['key' => $key, 'label' => $label];
        }
        if (! empty($player['faction_id'])) {
            $faction = db_connect()->table('factions')
                ->select('id, name, tag')
                ->where('id', (int) $player['faction_id'])
                ->get()->getRowArray();
            if ($faction !== null) {
                $out[] = [
                    'key'   => 'faction-' . (int) $faction['id'],
                    'label' => '[' . $faction['tag'] . ']',
                ];
            }
        }
        return $out;
    }

    /** Remplace les mots blacklist par ***. */
    private function censor(string $body, string $blacklistCsv): string
    {
        $words = array_filter(array_map('trim', explode(',', $blacklistCsv)), static fn($w) => $w !== '');
        foreach ($words as $w) {
            $body = preg_replace_callback(
                '/\b' . preg_quote($w, '/') . '\b/iu',
                static fn($m) => str_repeat('*', mb_strlen($m[0])),
                $body,
            ) ?? $body;
        }
        return $body;
    }
}
