<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class FactionWarModel extends Model
{
    protected $table         = 'faction_wars';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'faction_a_id', 'faction_b_id', 'status',
        'stake_a', 'stake_b', 'score_a', 'score_b',
        'declared_at', 'accepted_at', 'ends_at', 'ended_at',
    ];

    /** Guerre active OU pending pour cette faction (1 max, enforce code-cote). */
    public function activeForFaction(int $factionId): ?array
    {
        $row = $this->groupStart()
                ->where('faction_a_id', $factionId)
                ->orWhere('faction_b_id', $factionId)
            ->groupEnd()
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }

    /** Liste publique : actives + recently ended. */
    public function listVisible(int $limit = 50): array
    {
        return $this->select('faction_wars.*,
                              fa.name AS faction_a_name, fa.tag AS faction_a_tag,
                              fb.name AS faction_b_name, fb.tag AS faction_b_tag')
            ->join('factions fa', 'fa.id = faction_wars.faction_a_id', 'inner')
            ->join('factions fb', 'fb.id = faction_wars.faction_b_id', 'inner')
            ->whereIn('faction_wars.status', ['pending', 'active', 'ended_a_won', 'ended_b_won', 'ended_draw'])
            ->orderBy('faction_wars.status', 'ASC') // active avant ended
            ->orderBy('faction_wars.id', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Declare une guerre : debite stake de la treasury A, insere row pending.
     *
     * @return array{ok: bool, message: string, war_id?: int}
     */
    public function declare(int $factionAId, int $factionBId, int $leaderId): array
    {
        if ($factionAId === $factionBId) {
            return ['ok' => false, 'message' => 'Tu ne peux pas te declarer la guerre a toi-meme.'];
        }
        $factionModel = model(FactionModel::class);
        $a = $factionModel->find($factionAId);
        $b = $factionModel->find($factionBId);
        if ($a === null || $b === null) {
            return ['ok' => false, 'message' => 'Faction introuvable.'];
        }
        if ((int) $a['leader_player_id'] !== $leaderId) {
            return ['ok' => false, 'message' => 'Seul le leader peut declarer la guerre.'];
        }
        if ($this->activeForFaction($factionAId) !== null) {
            return ['ok' => false, 'message' => 'Ta faction a deja une guerre en cours ou en attente.'];
        }
        if ($this->activeForFaction($factionBId) !== null) {
            return ['ok' => false, 'message' => 'La cible a deja une guerre en cours ou en attente.'];
        }

        $stake = (int) model(GameSettingModel::class)->get('war_stake_credits', 100000);
        if ((int) $a['treasury'] < $stake) {
            return ['ok' => false, 'message' => 'Tresorerie insuffisante pour la mise (' . number_format($stake) . '¢).'];
        }

        $db = db_connect();
        $db->transStart();

        // Debit treasury A atomique : guard treasury >= stake.
        $db->table('factions')
            ->where('id', $factionAId)
            ->where('treasury >=', $stake)
            ->update([
                'treasury'   => new RawSql('treasury - ' . $stake),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Tresorerie insuffisante au moment du debit.'];
        }

        $this->insert([
            'faction_a_id' => $factionAId,
            'faction_b_id' => $factionBId,
            'status'       => 'pending',
            'stake_a'      => $stake,
            'declared_at'  => Time::now()->toDateTimeString(),
        ]);
        $warId = (int) $this->getInsertID();

        $db->transComplete();

        return ['ok' => true, 'message' => 'Guerre declaree, en attente de reponse de ' . esc($b['name']) . '.', 'war_id' => $warId];
    }

    /**
     * Accepte une guerre pending : debite stake B, active la guerre, fixe ends_at.
     *
     * @return array{ok: bool, message: string}
     */
    public function accept(int $warId, int $leaderId): array
    {
        $war = $this->find($warId);
        if ($war === null || $war['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Declaration introuvable ou deja traitee.'];
        }
        $factionB = model(FactionModel::class)->find((int) $war['faction_b_id']);
        if ($factionB === null || (int) $factionB['leader_player_id'] !== $leaderId) {
            return ['ok' => false, 'message' => 'Seul le leader de la faction visee peut accepter.'];
        }
        $settings    = model(GameSettingModel::class);
        $stake       = (int) $war['stake_a'];
        $durationHrs = (int) $settings->get('war_duration_hours', 168);
        if ((int) $factionB['treasury'] < $stake) {
            return ['ok' => false, 'message' => 'Tresorerie insuffisante pour egaliser la mise (' . number_format($stake) . '¢).'];
        }

        $db = db_connect();
        $db->transStart();

        $db->table('factions')
            ->where('id', (int) $war['faction_b_id'])
            ->where('treasury >=', $stake)
            ->update([
                'treasury'   => new RawSql('treasury - ' . $stake),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Tresorerie insuffisante au moment du debit.'];
        }

        // Transition pending -> active atomique.
        $now = Time::now();
        $this->builder()
            ->where('id', $warId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'active',
                'stake_b'     => $stake,
                'accepted_at' => $now->toDateTimeString(),
                'ends_at'     => $now->addHours($durationHrs)->toDateTimeString(),
                'updated_at'  => $now->toDateTimeString(),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Declaration deja traitee entre-temps.'];
        }

        $db->transComplete();
        return ['ok' => true, 'message' => 'Guerre acceptee. Que les meilleurs gagnent.'];
    }

    /**
     * Refuse une guerre pending : status = cancelled, refund A.
     */
    public function reject(int $warId, int $leaderId): array
    {
        $war = $this->find($warId);
        if ($war === null || $war['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Declaration introuvable ou deja traitee.'];
        }
        $factionB = model(FactionModel::class)->find((int) $war['faction_b_id']);
        if ($factionB === null || (int) $factionB['leader_player_id'] !== $leaderId) {
            return ['ok' => false, 'message' => 'Seul le leader de la faction visee peut refuser.'];
        }

        $db = db_connect();
        $db->transStart();

        // Transition pending -> cancelled atomique.
        $this->builder()
            ->where('id', $warId)
            ->where('status', 'pending')
            ->update([
                'status'     => 'cancelled',
                'ended_at'   => Time::now()->toDateTimeString(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Deja traitee entre-temps.'];
        }

        // Refund treasury A.
        $db->table('factions')
            ->where('id', (int) $war['faction_a_id'])
            ->update([
                'treasury'   => new RawSql('treasury + ' . (int) $war['stake_a']),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $db->transComplete();
        return ['ok' => true, 'message' => 'Guerre refusee, mise rendue.'];
    }

    /**
     * Increment score : si target est membre d'une faction en guerre active contre
     * la faction de l'attaquant, +1 sur le bon cote. Atomique.
     */
    public function incrementScoreForHospitalize(int $attackerFactionId, int $targetFactionId): void
    {
        if ($attackerFactionId === 0 || $targetFactionId === 0 || $attackerFactionId === $targetFactionId) return;
        $war = $this->where('status', 'active')
            ->groupStart()
                ->groupStart()
                    ->where('faction_a_id', $attackerFactionId)
                    ->where('faction_b_id', $targetFactionId)
                ->groupEnd()
                ->orGroupStart()
                    ->where('faction_a_id', $targetFactionId)
                    ->where('faction_b_id', $attackerFactionId)
                ->groupEnd()
            ->groupEnd()
            ->first();
        if ($war === null) return;

        $col = (int) $war['faction_a_id'] === $attackerFactionId ? 'score_a' : 'score_b';
        $this->builder()
            ->where('id', (int) $war['id'])
            ->where('status', 'active')
            ->update([
                $col         => new RawSql($col . ' + 1'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Cherche les guerres a terminer (score_cap atteint OU ends_at expire), les end,
     * et reverse le pot au vainqueur (50/50 si draw).
     *
     * Appele depuis TickCommand.
     *
     * @return int nombre de guerres terminees
     */
    public function endExpiredOrCapped(): int
    {
        $scoreCap = (int) model(GameSettingModel::class)->get('war_score_cap', 100);
        $now      = Time::now()->toDateTimeString();
        $pendingExpireHrs = (int) model(GameSettingModel::class)->get('war_pending_expire_hours', 24);

        // 1. Auto-cancel pending expirees : status -> cancelled, refund A.
        $expiredPending = $this->where('status', 'pending')
            ->where('declared_at <', Time::now()->subHours($pendingExpireHrs)->toDateTimeString())
            ->findAll();
        foreach ($expiredPending as $w) {
            $db = db_connect();
            $db->transStart();
            $this->builder()
                ->where('id', (int) $w['id'])
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'ended_at' => $now, 'updated_at' => $now]);
            $db->table('factions')
                ->where('id', (int) $w['faction_a_id'])
                ->update([
                    'treasury'   => new RawSql('treasury + ' . (int) $w['stake_a']),
                    'updated_at' => $now,
                ]);
            $db->transComplete();
        }

        // 2. End actives : score_cap atteint OU ends_at expire.
        $toEnd = $this->where('status', 'active')
            ->groupStart()
                ->where('score_a >=', $scoreCap)
                ->orWhere('score_b >=', $scoreCap)
                ->orWhere('ends_at <=', $now)
            ->groupEnd()
            ->findAll();

        $ended = 0;
        foreach ($toEnd as $w) {
            $a = (int) $w['score_a']; $b = (int) $w['score_b'];
            $pot = (int) $w['stake_a'] + (int) $w['stake_b'];
            $status = 'ended_draw';
            if ($a > $b) $status = 'ended_a_won';
            elseif ($b > $a) $status = 'ended_b_won';

            $db = db_connect();
            $db->transStart();

            $this->builder()
                ->where('id', (int) $w['id'])
                ->where('status', 'active')
                ->update(['status' => $status, 'ended_at' => $now, 'updated_at' => $now]);
            if ($db->affectedRows() === 0) {
                $db->transRollback();
                continue;
            }

            if ($status === 'ended_a_won') {
                $db->table('factions')->where('id', (int) $w['faction_a_id'])
                    ->update(['treasury' => new RawSql('treasury + ' . $pot), 'updated_at' => $now]);
            } elseif ($status === 'ended_b_won') {
                $db->table('factions')->where('id', (int) $w['faction_b_id'])
                    ->update(['treasury' => new RawSql('treasury + ' . $pot), 'updated_at' => $now]);
            } else {
                // Draw : split egal (tronque le solde si pot impair).
                $half = intdiv($pot, 2);
                $db->table('factions')->where('id', (int) $w['faction_a_id'])
                    ->update(['treasury' => new RawSql('treasury + ' . $half), 'updated_at' => $now]);
                $db->table('factions')->where('id', (int) $w['faction_b_id'])
                    ->update(['treasury' => new RawSql('treasury + ' . $half), 'updated_at' => $now]);
            }

            $db->transComplete();
            $ended++;
        }

        return $ended;
    }
}
