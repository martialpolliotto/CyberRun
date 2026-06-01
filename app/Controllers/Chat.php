<?php

namespace App\Controllers;

use App\Models\ChatMessageModel;
use App\Models\PlayerModel;
use App\Models\PlayerRelationModel;
use App\Services\ChatService;

/**
 * Chat en direct, polling HTMX. Pas de WebSocket.
 *  - /chat                       : page principale, channel par defaut 'global'
 *  - /chat/{channel}             : page sur un channel donne (ex: faction-12)
 *  - /chat/poll/{channel}/{lastId} : partial HTML pour les messages id > lastId
 *  - /chat/send (POST)           : envoie un message dans un channel
 */
class Chat extends BaseController
{
    public function index(?string $channel = null)
    {
        $me = $this->requireMe();
        $chat = new ChatService();

        $channels = $chat->visibleChannels($me);
        $channel  = $channel ?? ChatService::CHANNEL_GLOBAL;
        if (! $chat->canPostToChannel($me, $channel)) {
            return redirect()->to('/chat')->with('error', 'Channel inaccessible.');
        }

        $messages = model(ChatMessageModel::class)->latest($channel, 50);
        $muted    = $this->mutedPlayerIds((int) $me['id']);

        return view('chat/index', [
            'me'        => $me,
            'channels'  => $channels,
            'channel'   => $channel,
            'messages'  => array_values(array_filter($messages, static fn($m) => ! in_array((int) $m['sender_player_id'], $muted, true))),
            'last_id'   => empty($messages) ? 0 : (int) end($messages)['id'],
        ]);
    }

    /** Load initial (50 derniers) pour le widget HTMX. Renvoie le partial _messages. */
    public function init(string $channel)
    {
        $me   = $this->requireMe();
        $chat = new ChatService();
        if (! $chat->canPostToChannel($me, $channel)) {
            return $this->response->setStatusCode(403)->setBody('');
        }
        $msgs  = model(ChatMessageModel::class)->latest($channel, 50);
        $muted = $this->mutedPlayerIds((int) $me['id']);
        $msgs  = array_values(array_filter($msgs, static fn($m) => ! in_array((int) $m['sender_player_id'], $muted, true)));
        $lastId = empty($msgs) ? 0 : (int) end($msgs)['id'];

        return view('chat/_messages', [
            'me'       => $me,
            'channel'  => $channel,
            'messages' => $msgs,
            'last_id'  => $lastId,
        ]);
    }

    /** Renvoie le HTML des messages id > lastId. Style HTMX (swap=beforeend dans le DOM list). */
    public function poll(string $channel, int $lastId)
    {
        $me   = $this->requireMe();
        $chat = new ChatService();
        if (! $chat->canPostToChannel($me, $channel)) {
            return $this->response->setStatusCode(403)->setBody('');
        }

        $msgs  = model(ChatMessageModel::class)->fetchSince($channel, $lastId, 100);
        $muted = $this->mutedPlayerIds((int) $me['id']);
        $msgs  = array_values(array_filter($msgs, static fn($m) => ! in_array((int) $m['sender_player_id'], $muted, true)));
        $newLastId = empty($msgs) ? $lastId : (int) end($msgs)['id'];

        return view('chat/_messages', [
            'me'       => $me,
            'channel'  => $channel,
            'messages' => $msgs,
            'last_id'  => $newLastId,
        ]);
    }

    public function send()
    {
        $me      = $this->requireMe();
        $channel = (string) $this->request->getPost('channel');
        $body    = (string) $this->request->getPost('body');

        $r = (new ChatService())->send((int) $me['id'], $channel, $body);

        if ($this->isHtmx()) {
            // En HTMX, on renvoie un trigger pour pousser le polling, et un eventuel erreur.
            $resp = $this->response;
            if (! $r['ok']) {
                $resp->setHeader('HX-Trigger', json_encode(['chatError' => $r['message']]));
            } else {
                $resp->setHeader('HX-Trigger', 'chatSent');
            }
            return $resp->setBody('');
        }

        return redirect()->to('/chat/' . $channel)
            ->with($r['ok'] ? 'message' : 'error', $r['ok'] ? '' : $r['message']);
    }

    /** Ids des joueurs marques 'enemy' par moi -> messages masques cote client. */
    private function mutedPlayerIds(int $myPlayerId): array
    {
        $rows = model(PlayerRelationModel::class)
            ->where('player_id', $myPlayerId)
            ->where('relation_type', 'enemy')
            ->findAll();
        return array_map(static fn($r) => (int) $r['target_player_id'], $rows);
    }

    private function requireMe(): array
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            throw new \RuntimeException('Fiche player introuvable.');
        }
        return $me;
    }
}
