<?php

namespace App\Models;

use CodeIgniter\Model;

class FactionApplicationModel extends Model
{
    protected $table         = 'faction_applications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'faction_id', 'player_id', 'message', 'status',
        'decided_by_player_id', 'decided_at',
    ];

    public const MAX_MESSAGE = 500;

    /**
     * Apply : 1 candidature 'pending' max par joueur (toutes factions confondues).
     *
     * @return array{ok: bool, message: string, application_id?: int}
     */
    public function apply(int $playerId, int $factionId, ?string $message): array
    {
        $playerModel = model(PlayerModel::class);
        $player = $playerModel->find($playerId);
        if ($player === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }
        if (! empty($player['faction_id'])) {
            return ['ok' => false, 'message' => 'Tu fais deja partie d\'une faction.'];
        }

        $factionModel = model(FactionModel::class);
        $faction = $factionModel->find($factionId);
        if ($faction === null) {
            return ['ok' => false, 'message' => 'Faction introuvable.'];
        }
        $maxMembers = (int) model(GameSettingModel::class)->get('faction_max_members', 50);
        if ((int) $faction['members_count'] >= $maxMembers) {
            return ['ok' => false, 'message' => 'Cette faction est complete (' . $maxMembers . ' membres).'];
        }

        // 1 pending par joueur, toutes factions confondues.
        $existing = $this->where('player_id', $playerId)->where('status', 'pending')->first();
        if ($existing !== null) {
            return ['ok' => false, 'message' => 'Tu as deja une candidature en attente. Annule-la pour postuler ailleurs.'];
        }

        $clean = $message !== null ? trim($message) : null;
        if ($clean !== null && mb_strlen($clean) > self::MAX_MESSAGE) {
            $clean = mb_substr($clean, 0, self::MAX_MESSAGE);
        }

        $this->insert([
            'faction_id' => $factionId,
            'player_id'  => $playerId,
            'message'    => $clean ?: null,
            'status'     => 'pending',
        ]);

        return ['ok' => true, 'message' => 'Candidature envoyee.', 'application_id' => (int) $this->getInsertID()];
    }

    /** Pending applications visibles par le leader d'une faction. */
    public function listPendingForFaction(int $factionId): array
    {
        return $this->select('faction_applications.*, users.username, players.level')
            ->join('players', 'players.id = faction_applications.player_id', 'inner')
            ->join('users',   'users.id   = players.user_id', 'inner')
            ->where('faction_applications.faction_id', $factionId)
            ->where('faction_applications.status', 'pending')
            ->orderBy('faction_applications.created_at', 'ASC')
            ->findAll();
    }

    /** Ma candidature pending courante (si elle existe). */
    public function pendingForPlayer(int $playerId): ?array
    {
        $row = $this->select('faction_applications.*, factions.name AS faction_name, factions.tag AS faction_tag')
            ->join('factions', 'factions.id = faction_applications.faction_id', 'inner')
            ->where('faction_applications.player_id', $playerId)
            ->where('faction_applications.status', 'pending')
            ->first();
        return $row ?: null;
    }

    /**
     * Accept : status=accepted + addMember + reject auto les autres pending du candidat.
     *
     * @return array{ok: bool, message: string}
     */
    public function accept(int $applicationId, int $leaderPlayerId): array
    {
        $app = $this->find($applicationId);
        if ($app === null || $app['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Candidature introuvable ou deja traitee.'];
        }

        $faction = model(FactionModel::class)->find((int) $app['faction_id']);
        if ($faction === null) {
            return ['ok' => false, 'message' => 'Faction introuvable.'];
        }
        if ((int) $faction['leader_player_id'] !== $leaderPlayerId) {
            return ['ok' => false, 'message' => 'Seul le leader peut accepter une candidature.'];
        }
        $maxMembers = (int) model(GameSettingModel::class)->get('faction_max_members', 50);
        if ((int) $faction['members_count'] >= $maxMembers) {
            return ['ok' => false, 'message' => 'Faction complete.'];
        }

        $candidate = model(PlayerModel::class)->find((int) $app['player_id']);
        if ($candidate === null) {
            return ['ok' => false, 'message' => 'Candidat introuvable.'];
        }
        if (! empty($candidate['faction_id'])) {
            // Le candidat a deja rejoint ailleurs entre-temps.
            $this->update($applicationId, [
                'status'               => 'cancelled',
                'decided_by_player_id' => $leaderPlayerId,
                'decided_at'           => date('Y-m-d H:i:s'),
            ]);
            return ['ok' => false, 'message' => 'Le candidat a deja rejoint une autre faction.'];
        }

        $db = db_connect();
        $db->transStart();

        $this->update($applicationId, [
            'status'               => 'accepted',
            'decided_by_player_id' => $leaderPlayerId,
            'decided_at'           => date('Y-m-d H:i:s'),
        ]);

        model(FactionMemberModel::class)->addMember((int) $app['faction_id'], (int) $app['player_id'], 'member');

        // Annule toute autre pending du candidat (defense en profondeur).
        $this->where('player_id', (int) $app['player_id'])
             ->where('status', 'pending')
             ->where('id !=', $applicationId)
             ->set([
                 'status'               => 'cancelled',
                 'decided_by_player_id' => $leaderPlayerId,
                 'decided_at'           => date('Y-m-d H:i:s'),
                 'updated_at'           => date('Y-m-d H:i:s'),
             ])->update();

        $db->transComplete();

        return ['ok' => true, 'message' => 'Candidature acceptee.'];
    }

    /** Reject : status=rejected. */
    public function reject(int $applicationId, int $leaderPlayerId): array
    {
        $app = $this->find($applicationId);
        if ($app === null || $app['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Candidature introuvable ou deja traitee.'];
        }
        $faction = model(FactionModel::class)->find((int) $app['faction_id']);
        if ($faction === null || (int) $faction['leader_player_id'] !== $leaderPlayerId) {
            return ['ok' => false, 'message' => 'Action reservee au leader.'];
        }
        $this->update($applicationId, [
            'status'               => 'rejected',
            'decided_by_player_id' => $leaderPlayerId,
            'decided_at'           => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'message' => 'Candidature refusee.'];
    }

    /** Cancel : par le joueur lui-meme. */
    public function cancel(int $applicationId, int $playerId): array
    {
        $app = $this->find($applicationId);
        if ($app === null || $app['status'] !== 'pending' || (int) $app['player_id'] !== $playerId) {
            return ['ok' => false, 'message' => 'Candidature introuvable ou deja traitee.'];
        }
        $this->update($applicationId, [
            'status'               => 'cancelled',
            'decided_by_player_id' => $playerId,
            'decided_at'           => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'message' => 'Candidature annulee.'];
    }
}
