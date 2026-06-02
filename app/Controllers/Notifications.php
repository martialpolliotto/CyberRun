<?php

namespace App\Controllers;

use App\Models\CombatModel;
use App\Models\MessageModel;
use App\Models\PlayerModel;

/**
 * Endpoint poll pour les notifications navigateur.
 *
 * GET /notifications/poll?since=ISO8601
 * Renvoie un JSON des evenements survenus depuis $since pour le user logge.
 * Le client gere : Notification API, mise en file, click -> navigate.
 *
 * Sources d'evenements (MVP) :
 *  - new_messages : messages prives recus apres $since
 *  - new_attacks  : combats ended ou j'etais defenseur, apres $since
 */
class Notifications extends BaseController
{
    public function poll()
    {
        $me = $this->me();
        if ($me === null) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'not_logged_in']);
        }

        // Defaut : -5 min si pas de since (= la fenetre que le client n'a pas vue).
        $since = (string) ($this->request->getGet('since') ?? '');
        if ($since === '' || strtotime($since) === false) {
            $since = date('Y-m-d H:i:s', time() - 300);
        }

        // Messages prives recus apres $since.
        $newMessages = db_connect()->table('messages m')
            ->select('m.id, m.body, m.created_at, m.sender_player_id, users.username AS sender_username')
            ->join('players', 'players.id = m.sender_player_id', 'inner')
            ->join('users',   'users.id   = players.user_id', 'inner')
            ->where('m.recipient_player_id', (int) $me['id'])
            ->where('m.created_at >', $since)
            ->orderBy('m.id', 'ASC')
            ->limit(10)
            ->get()->getResultArray();

        $messagesOut = array_map(static fn(array $m): array => [
            'id'        => (int) $m['id'],
            'sender'    => (string) $m['sender_username'],
            'preview'   => mb_substr((string) $m['body'], 0, 80),
            'created_at'=> (string) $m['created_at'],
        ], $newMessages);

        // Combats ended ou j'etais defenseur, apres $since.
        $newAttacks = db_connect()->table('combats c')
            ->select('c.id, c.ended_at, c.status, c.post_action, c.mug_amount,
                      c.attacker_player_id, users.username AS attacker_username')
            ->join('players', 'players.id = c.attacker_player_id', 'inner')
            ->join('users',   'users.id   = players.user_id',      'inner')
            ->where('c.defender_player_id', (int) $me['id'])
            ->where('c.status !=', 'ongoing')
            ->where('c.ended_at >', $since)
            ->orderBy('c.id', 'ASC')
            ->limit(10)
            ->get()->getResultArray();

        $attacksOut = array_map(static function (array $c): array {
            $outcome = match ($c['post_action']) {
                'hospitalize' => 'hospitalisé',
                'mug'         => 'volé ' . number_format((int) $c['mug_amount']) . '¢',
                'leave'       => 'parti',
                default       => 'duel',
            };
            return [
                'id'         => (int) $c['id'],
                'attacker'   => (string) $c['attacker_username'],
                'outcome'    => $outcome,
                'ended_at'   => (string) $c['ended_at'],
            ];
        }, $newAttacks);

        return $this->response->setJSON([
            'ok'       => true,
            'since'    => $since,
            'now'      => date('Y-m-d H:i:s'),
            'messages' => $messagesOut,
            'attacks'  => $attacksOut,
        ]);
    }
}
