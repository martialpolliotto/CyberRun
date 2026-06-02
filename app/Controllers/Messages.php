<?php

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\PlayerModel;

/**
 * Messagerie privee 1-to-1 entre joueurs. Modele Torn mail :
 *   /messages            -> inbox (liste des conversations)
 *   /messages/thread/X   -> conversation avec le player X + form d'envoi
 *   /messages/send       -> POST envoi
 */
class Messages extends BaseController
{
    /** Inbox : liste des threads. */
    public function index()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        model(\App\Models\MissionModel::class)->trackEvent((int) $me['id'], 'visit_page', 'messages');

        return view('messages/index', [
            'me'      => $me,
            'threads' => model(MessageModel::class)->listThreads((int) $me['id']),
        ]);
    }

    /** Conversation 1-to-1. Marque les messages recus comme lus a l'arrivee. */
    public function thread(int $partnerPlayerId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        if ($partnerPlayerId === (int) $me['id']) {
            return redirect()->to('/messages')->with('error', 'Tu ne peux pas converser avec toi-meme.');
        }

        $partner = $this->fetchPartner($partnerPlayerId);
        if ($partner === null) {
            return redirect()->to('/messages')->with('error', 'Joueur introuvable.');
        }

        $messageModel = model(MessageModel::class);
        $messageModel->markThreadRead((int) $me['id'], $partnerPlayerId);

        return view('messages/thread', [
            'me'       => $me,
            'partner'  => $partner,
            'messages' => $messageModel->thread((int) $me['id'], $partnerPlayerId),
        ]);
    }

    /** POST: envoie un message dans le thread. */
    public function send()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $recipientId = (int) $this->request->getPost('recipient_player_id');
        $body        = (string) $this->request->getPost('body');

        $r = model(MessageModel::class)->send((int) $me['id'], $recipientId, $body);

        return redirect()->to('/messages/thread/' . $recipientId)
            ->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    /** @return array{id:int, user_id:int, username:string}|null */
    private function fetchPartner(int $playerId): ?array
    {
        $row = db_connect()->table('players p')
            ->select('p.id, p.user_id, users.username')
            ->join('users', 'users.id = p.user_id', 'inner')
            ->where('p.id', $playerId)
            ->get()->getRowArray();
        if ($row === null) return null;
        return [
            'id'       => (int) $row['id'],
            'user_id'  => (int) $row['user_id'],
            'username' => (string) $row['username'],
        ];
    }
}
