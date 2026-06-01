<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

class BountyModel extends Model
{
    protected $table         = 'bounties';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'placer_player_id', 'target_player_id', 'amount', 'message',
        'status', 'claimed_by_player_id', 'claimed_at',
    ];

    public const STATUSES = ['active', 'claimed', 'cancelled'];

    /**
     * Place une prime sur target. Debite le placer atomiquement, insere la bounty.
     *
     * @return array{ok: bool, message: string, bounty_id?: int}
     */
    public function place(int $placerId, int $targetId, int $amount, ?string $note = null): array
    {
        if ($placerId === $targetId) {
            return ['ok' => false, 'message' => 'Tu ne peux pas placer une prime sur toi-meme.'];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'message' => 'Montant invalide.'];
        }

        $playerModel = model(PlayerModel::class);
        $placer = $playerModel->find($placerId);
        $target = $playerModel->find($targetId);
        if ($placer === null || $target === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }
        if ((int) $placer['credits'] < $amount) {
            return ['ok' => false, 'message' => 'Credits insuffisants.'];
        }

        $db = db_connect();
        $db->transStart();

        // Debit credits placer (atomique).
        $playerModel->builder()
            ->where('id', $placerId)
            ->where('credits >=', $amount)
            ->update([
                'credits'    => new RawSql('credits - ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Credits insuffisants au moment du placement.'];
        }

        $this->insert([
            'placer_player_id' => $placerId,
            'target_player_id' => $targetId,
            'amount'           => $amount,
            'message'          => $note,
            'status'           => 'active',
        ]);
        $bountyId = $this->getInsertID();

        $db->transComplete();

        return ['ok' => true, 'message' => 'Prime de ' . $amount . ' credits placee.', 'bounty_id' => (int) $bountyId];
    }

    /**
     * Annule une bounty par son placeur. Refund atomique des credits.
     * Le UPDATE conditionne sur placer + status='active' verrouille la transition
     * et empeche un double refund / un cancel concurrent avec un claim.
     *
     * @return array{ok: bool, message: string, refunded?: int}
     */
    public function cancel(int $bountyId, int $placerId): array
    {
        $bounty = $this->find($bountyId);
        if ($bounty === null || (int) $bounty['placer_player_id'] !== $placerId) {
            return ['ok' => false, 'message' => 'Prime introuvable ou pas la tienne.'];
        }
        if ($bounty['status'] !== 'active') {
            return ['ok' => false, 'message' => 'Cette prime n\'est plus active.'];
        }

        $amount = (int) $bounty['amount'];
        $db     = db_connect();
        $db->transStart();

        // Transition active -> cancelled atomique : si quelqu'un a claim entre-temps,
        // affectedRows = 0 et on rollback sans refund.
        $this->builder()
            ->where('id', $bountyId)
            ->where('placer_player_id', $placerId)
            ->where('status', 'active')
            ->update([
                'status'     => 'cancelled',
                'claimed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Prime deja claim ou annulee entre-temps.'];
        }

        model(PlayerModel::class)->builder()
            ->where('id', $placerId)
            ->update([
                'credits'    => new RawSql('credits + ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $db->transComplete();
        return ['ok' => true, 'message' => 'Prime annulee, ' . number_format($amount) . ' credits rembourses.', 'refunded' => $amount];
    }

    /**
     * Bounties actives sur une cible.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeOnTarget(int $targetId): array
    {
        return $this->select('bounties.*, users.username AS placer_username')
            ->join('players', 'players.id = bounties.placer_player_id', 'inner')
            ->join('users',   'users.id = players.user_id', 'inner')
            ->where('bounties.target_player_id', $targetId)
            ->where('bounties.status', 'active')
            ->orderBy('bounties.amount', 'DESC')
            ->findAll();
    }

    /** Liste globale des bounties actives, triees par montant decroissant. */
    public function listActive(int $limit = 50): array
    {
        return $this->select('bounties.*, target_users.username AS target_username, placer_users.username AS placer_username')
            ->join('players target_p', 'target_p.id = bounties.target_player_id', 'inner')
            ->join('users target_users', 'target_users.id = target_p.user_id', 'inner')
            ->join('players placer_p', 'placer_p.id = bounties.placer_player_id', 'inner')
            ->join('users placer_users', 'placer_users.id = placer_p.user_id', 'inner')
            ->where('bounties.status', 'active')
            ->orderBy('bounties.amount', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
