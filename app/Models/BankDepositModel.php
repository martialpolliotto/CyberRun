<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class BankDepositModel extends Model
{
    protected $table         = 'bank_deposits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'player_id', 'amount', 'duration_days', 'interest_pct',
        'deposited_at', 'matures_at', 'withdrawn_at', 'withdrawn_amount',
    ];

    /**
     * Cree un depot : debit atomique du wallet + insert. Atomique via PlayerModel::debitAtomic.
     *
     * @return array{ok: bool, message: string, deposit_id?: int}
     */
    public function deposit(int $playerId, int $amount, int $durationDays, float $interestPct): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'message' => 'Montant invalide.'];
        }
        if ($durationDays <= 0) {
            return ['ok' => false, 'message' => 'Durée invalide.'];
        }

        $settings = model(GameSettingModel::class);
        $maxActive = (int) $settings->get('bank_max_active_deposits', 10);
        $activeCount = $this->where('player_id', $playerId)
            ->where('withdrawn_at', null)
            ->countAllResults();
        if ($activeCount >= $maxActive) {
            return ['ok' => false, 'message' => 'Limite ' . $maxActive . ' dépôts actifs atteinte. Retire-en un d\'abord.'];
        }

        $playerModel = model(PlayerModel::class);
        $db = db_connect();
        $db->transStart();

        if (! $playerModel->debitAtomic($playerId, $amount)) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Crédits insuffisants.'];
        }

        $now = Time::now();
        $this->insert([
            'player_id'    => $playerId,
            'amount'       => $amount,
            'duration_days'=> $durationDays,
            'interest_pct' => $interestPct,
            'deposited_at' => $now->toDateTimeString(),
            'matures_at'   => $now->addDays($durationDays)->toDateTimeString(),
        ]);
        $depositId = (int) $this->getInsertID();

        $db->transComplete();
        return ['ok' => true, 'message' => 'Dépôt de ' . number_format($amount) . '¢ pour ' . $durationDays . ' jours.', 'deposit_id' => $depositId];
    }

    /**
     * Retire un depot mature : principal + interet. Refuse si pas encore mature
     * (depot verrouille jusqu'a maturite, plus de sortie anticipee).
     *
     * @return array{ok: bool, message: string, payout?: int}
     */
    public function withdraw(int $depositId, int $playerId): array
    {
        $row = $this->find($depositId);
        if ($row === null || (int) $row['player_id'] !== $playerId) {
            return ['ok' => false, 'message' => 'Dépôt introuvable.'];
        }
        if ($row['withdrawn_at'] !== null) {
            return ['ok' => false, 'message' => 'Dépôt déjà retiré.'];
        }

        $now = Time::now();
        if (Time::parse($row['matures_at'])->isAfter($now)) {
            return ['ok' => false, 'message' => 'Dépôt verrouillé jusqu\'à ' . substr((string) $row['matures_at'], 0, 16) . '.'];
        }

        $principal = (int) $row['amount'];
        $interest  = (int) floor($principal * (float) $row['interest_pct'] / 100);
        $payout    = $principal + $interest;

        $db = db_connect();
        $db->transStart();

        // Transition active -> withdrawn atomique avec guard matures_at <= NOW().
        $this->builder()
            ->where('id', $depositId)
            ->where('player_id', $playerId)
            ->where('withdrawn_at IS NULL', null, false)
            ->where('matures_at <=', $now->toDateTimeString())
            ->update([
                'withdrawn_at'     => $now->toDateTimeString(),
                'withdrawn_amount' => $payout,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Retrait en cours ou dépôt pas encore maturé.'];
        }

        model(PlayerModel::class)->creditUnconditional($playerId, $payout);
        $db->transComplete();

        return [
            'ok'      => true,
            'message' => 'Retiré : ' . number_format($payout) . '¢ (principal ' . number_format($principal) . ' + intérêt ' . number_format($interest) . ').',
            'payout'  => $payout,
        ];
    }

    /**
     * Liste des depots du joueur avec statut derive (active / matured / withdrawn) et payout estime.
     */
    public function listForPlayer(int $playerId): array
    {
        $rows = $this->where('player_id', $playerId)
            ->orderBy('id', 'DESC')
            ->findAll();
        $now = Time::now();
        foreach ($rows as &$r) {
            if ($r['withdrawn_at'] !== null) {
                $r['_status'] = 'withdrawn';
            } else {
                $r['_status'] = Time::parse($r['matures_at'])->isBefore($now) ? 'matured' : 'active';
            }
            $r['_interest']      = (int) floor((int) $r['amount'] * (float) $r['interest_pct'] / 100);
            $r['_payout_now']    = $r['_status'] === 'matured' ? ((int) $r['amount'] + $r['_interest']) : (int) $r['amount'];
            $r['_payout_mature'] = (int) $r['amount'] + $r['_interest'];
        }
        unset($r);
        return $rows;
    }
}
