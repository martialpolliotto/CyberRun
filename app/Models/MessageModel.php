<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

/**
 * Messagerie privee 1-to-1. Pas de table threads : on derive a la volee.
 */
class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'sender_player_id', 'recipient_player_id', 'body', 'read_at',
    ];

    public const MAX_BODY = 2000;

    /**
     * Envoie un message. Le destinataire le voit comme unread.
     *
     * @return array{ok: bool, message: string, message_id?: int}
     */
    public function send(int $senderId, int $recipientId, string $body): array
    {
        $body = trim($body);
        if ($senderId === $recipientId) {
            return ['ok' => false, 'message' => 'Tu ne peux pas t\'envoyer un message a toi-meme.'];
        }
        if ($body === '') {
            return ['ok' => false, 'message' => 'Message vide.'];
        }
        if (mb_strlen($body) > self::MAX_BODY) {
            return ['ok' => false, 'message' => 'Message trop long (max ' . self::MAX_BODY . ' caracteres).'];
        }

        $playerModel = model(PlayerModel::class);
        if ($playerModel->find($recipientId) === null) {
            return ['ok' => false, 'message' => 'Destinataire introuvable.'];
        }

        $this->insert([
            'sender_player_id'    => $senderId,
            'recipient_player_id' => $recipientId,
            'body'                => $body,
        ]);

        return ['ok' => true, 'message' => 'Message envoye.', 'message_id' => (int) $this->getInsertID()];
    }

    /**
     * Compte les messages non lus pour un joueur. Lecture cheap (index).
     */
    public function unreadCount(int $playerId): int
    {
        return $this->where('recipient_player_id', $playerId)
            ->where('read_at', null)
            ->countAllResults();
    }

    /**
     * Liste des "threads" : 1 ligne par partenaire de conversation, avec dernier message
     * + compteur unread. Trie par activite recente.
     *
     * @return array<int, array{partner_player_id:int, partner_username:string, last_body:string, last_at:string, unread:int}>
     */
    public function listThreads(int $playerId): array
    {
        // Un thread = ensemble des messages echanges entre $playerId et un autre joueur.
        // L'identite du partenaire est l'autre cote du message (sender ou recipient).
        $db = db_connect();
        $sql = <<<SQL
            SELECT
                partner.id                                AS partner_player_id,
                u.username                                AS partner_username,
                last_msg.body                             AS last_body,
                last_msg.created_at                       AS last_at,
                COALESCE(unread.cnt, 0)                   AS unread
            FROM (
                SELECT
                    CASE WHEN sender_player_id = ? THEN recipient_player_id
                         ELSE sender_player_id END        AS partner_id,
                    MAX(id)                                AS last_msg_id
                FROM messages
                WHERE sender_player_id = ? OR recipient_player_id = ?
                GROUP BY partner_id
            ) t
            JOIN messages last_msg ON last_msg.id = t.last_msg_id
            JOIN players  partner  ON partner.id  = t.partner_id
            JOIN users    u        ON u.id        = partner.user_id
            LEFT JOIN (
                SELECT sender_player_id AS partner_id, COUNT(*) AS cnt
                FROM messages
                WHERE recipient_player_id = ? AND read_at IS NULL
                GROUP BY sender_player_id
            ) unread ON unread.partner_id = t.partner_id
            ORDER BY last_msg.created_at DESC
SQL;

        $query = $db->query($sql, [$playerId, $playerId, $playerId, $playerId]);
        return $query->getResultArray();
    }

    /**
     * Recupere le thread complet entre $playerId et $partnerId. Ordre chronologique.
     *
     * @return array<int, array<string, mixed>>
     */
    public function thread(int $playerId, int $partnerId): array
    {
        return $this->groupStart()
                ->where('sender_player_id', $playerId)
                ->where('recipient_player_id', $partnerId)
            ->groupEnd()
            ->orGroupStart()
                ->where('sender_player_id', $partnerId)
                ->where('recipient_player_id', $playerId)
            ->groupEnd()
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Marque tous les messages recus du partenaire comme lus pour $playerId.
     */
    public function markThreadRead(int $playerId, int $partnerId): void
    {
        $this->builder()
            ->where('recipient_player_id', $playerId)
            ->where('sender_player_id', $partnerId)
            ->where('read_at', null)
            ->update([
                'read_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
