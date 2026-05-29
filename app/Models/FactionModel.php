<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

class FactionModel extends Model
{
    protected $table         = 'factions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'tag', 'description', 'leader_player_id',
        'treasury', 'respect', 'members_count',
    ];

    public const NAME_MIN_LEN = 3;
    public const NAME_MAX_LEN = 80;
    public const TAG_MIN_LEN  = 2;
    public const TAG_MAX_LEN  = 8;

    /**
     * Cree une faction et inscrit le fondateur comme leader, atomique.
     *
     * @return array{ok: bool, message: string, faction_id?: int}
     */
    public function create(int $founderPlayerId, string $name, string $tag, ?string $description): array
    {
        $name = trim($name);
        $tag  = trim($tag);
        if (mb_strlen($name) < self::NAME_MIN_LEN || mb_strlen($name) > self::NAME_MAX_LEN) {
            return ['ok' => false, 'message' => 'Nom invalide (' . self::NAME_MIN_LEN . '-' . self::NAME_MAX_LEN . ' caracteres).'];
        }
        if (mb_strlen($tag) < self::TAG_MIN_LEN || mb_strlen($tag) > self::TAG_MAX_LEN) {
            return ['ok' => false, 'message' => 'Tag invalide (' . self::TAG_MIN_LEN . '-' . self::TAG_MAX_LEN . ' caracteres).'];
        }

        $settings = model(GameSettingModel::class);
        $cost     = (int) $settings->get('faction_create_cost', 100000);
        $minLevel = (int) $settings->get('faction_create_min_level', 5);

        $playerModel = model(PlayerModel::class);
        $founder     = $playerModel->find($founderPlayerId);
        if ($founder === null) {
            return ['ok' => false, 'message' => 'Joueur introuvable.'];
        }
        if ((int) $founder['level'] < $minLevel) {
            return ['ok' => false, 'message' => 'Niveau ' . $minLevel . ' minimum pour fonder une faction.'];
        }
        if ((int) $founder['credits'] < $cost) {
            return ['ok' => false, 'message' => 'Credits insuffisants (' . number_format($cost) . ' requis).'];
        }
        if (! empty($founder['faction_id'])) {
            return ['ok' => false, 'message' => 'Tu fais deja partie d\'une faction.'];
        }
        if ($this->where('name', $name)->first() !== null) {
            return ['ok' => false, 'message' => 'Ce nom est deja pris.'];
        }
        if ($this->where('tag', $tag)->first() !== null) {
            return ['ok' => false, 'message' => 'Ce tag est deja pris.'];
        }

        $db = db_connect();
        $db->transStart();

        // Debit credits atomique.
        $playerModel->builder()
            ->where('id', $founderPlayerId)
            ->where('credits >=', $cost)
            ->update([
                'credits'    => new RawSql('credits - ' . $cost),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Credits insuffisants au moment du debit.'];
        }

        $this->insert([
            'name'             => $name,
            'tag'              => $tag,
            'description'      => $description !== null ? trim($description) : null,
            'leader_player_id' => $founderPlayerId,
            'treasury'         => 0,
            'respect'          => 0,
            'members_count'    => 1,
        ]);
        $factionId = (int) $this->getInsertID();

        // Insere le fondateur comme leader.
        $db->table('faction_members')->insert([
            'faction_id' => $factionId,
            'player_id'  => $founderPlayerId,
            'rank'       => 'leader',
            'joined_at'  => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Marque le joueur comme membre.
        $playerModel->update($founderPlayerId, [
            'faction_id' => $factionId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        return ['ok' => true, 'message' => 'Faction « ' . $name . ' » fondee.', 'faction_id' => $factionId];
    }

    /**
     * Liste des factions, triees par respect decroissant.
     */
    public function listAll(int $limit = 100): array
    {
        return $this->select('factions.*, users.username AS leader_username')
            ->join('players',  'players.id = factions.leader_player_id', 'left')
            ->join('users',    'users.id = players.user_id', 'left')
            ->orderBy('factions.respect', 'DESC')
            ->orderBy('factions.members_count', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /** Faction + leader_username. */
    public function findWithLeader(int $factionId): ?array
    {
        $row = $this->select('factions.*, users.username AS leader_username')
            ->join('players',  'players.id = factions.leader_player_id', 'left')
            ->join('users',    'users.id = players.user_id', 'left')
            ->where('factions.id', $factionId)
            ->first();
        return $row ?: null;
    }

    /** Ajoute respect a la faction + a la contribution du membre. Atomique. */
    public function addRespect(int $factionId, int $playerId, int $amount): void
    {
        if ($amount <= 0) return;
        $this->builder()
            ->where('id', $factionId)
            ->update([
                'respect'    => new RawSql('respect + ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        db_connect()->table('faction_members')
            ->where('faction_id', $factionId)
            ->where('player_id',  $playerId)
            ->update([
                'contributed_respect' => new RawSql('contributed_respect + ' . $amount),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
    }
}
