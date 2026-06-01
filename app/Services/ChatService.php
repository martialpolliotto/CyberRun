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

        // Antiflood : 3 couches.
        $msgModel = model(ChatMessageModel::class);

        $hard = (int) $settings->get('chat_rate_hard_seconds', 2);
        if ($hard > 0 && $msgModel->recentSendCount($playerId, $hard) > 0) {
            return ['ok' => false, 'message' => 'Tu envoies trop vite. Attends ' . $hard . 's.'];
        }

        $burstCount = (int) $settings->get('chat_rate_burst_count', 5);
        $burstSecs  = (int) $settings->get('chat_rate_burst_seconds', 10);
        if ($burstCount > 0 && $burstSecs > 0 && $msgModel->recentSendCount($playerId, $burstSecs) >= $burstCount) {
            return ['ok' => false, 'message' => 'Trop de messages en rafale. Attends un peu.'];
        }

        $softCount = (int) $settings->get('chat_rate_soft_count', 10);
        $softSecs  = (int) $settings->get('chat_rate_soft_seconds', 60);
        if ($softCount > 0 && $softSecs > 0 && $msgModel->recentSendCount($playerId, $softSecs) >= $softCount) {
            return ['ok' => false, 'message' => 'Limite de ' . $softCount . ' messages/min atteinte.'];
        }

        // Censure (replace transparent).
        $body = $this->censor($body, (string) $settings->get('chat_blacklist_csv', ''));

        $msgModel->insert([
            'channel'          => $channel,
            'sender_player_id' => $playerId,
            'body'             => $body,
        ]);

        return ['ok' => true, 'message' => 'OK', 'message_id' => (int) $msgModel->getInsertID()];
    }

    /** Verifie qu'un joueur peut poster sur ce channel. */
    public function canPostToChannel(array $player, string $channel): bool
    {
        if (in_array($channel, [self::CHANNEL_GLOBAL, self::CHANNEL_NEWBIE, self::CHANNEL_TRADE, self::CHANNEL_COMPANY], true)) {
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
        $out = [
            ['key' => self::CHANNEL_GLOBAL,  'label' => 'Global'],
            ['key' => self::CHANNEL_TRADE,   'label' => 'Trade'],
            ['key' => self::CHANNEL_NEWBIE,  'label' => 'Débutants'],
            ['key' => self::CHANNEL_COMPANY, 'label' => 'Company'],
        ];
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
