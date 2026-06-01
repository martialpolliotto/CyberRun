<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

class FactionMemberModel extends Model
{
    protected $table         = 'faction_members';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'faction_id', 'player_id', 'rank',
        'contributed_credits', 'contributed_respect', 'joined_at',
    ];

    /** Liste des membres d'une faction avec username + level. Trie par rank puis respect. */
    public function listForFaction(int $factionId): array
    {
        return $this->select('faction_members.*, users.username, players.level, players.hp_current, players.hp_max')
            ->join('players', 'players.id = faction_members.player_id', 'inner')
            ->join('users',   'users.id   = players.user_id', 'inner')
            ->where('faction_members.faction_id', $factionId)
            ->orderBy('faction_members.rank', 'ASC') // leader < member alphabetiquement
            ->orderBy('faction_members.contributed_respect', 'DESC')
            ->findAll();
    }

    /** @return array<string,mixed>|null */
    public function findByPlayer(int $playerId): ?array
    {
        $row = $this->where('player_id', $playerId)->first();
        return $row ?: null;
    }

    /**
     * Ajoute un membre + incremente members_count de la faction. Atomique.
     * Assume que le joueur n'est pas deja dans une faction (check caller).
     */
    public function addMember(int $factionId, int $playerId, string $rank = 'member'): void
    {
        $db = db_connect();
        $db->transStart();
        $this->insert([
            'faction_id' => $factionId,
            'player_id'  => $playerId,
            'rank'       => $rank,
            'joined_at'  => date('Y-m-d H:i:s'),
        ]);
        model(PlayerModel::class)->update($playerId, [
            'faction_id' => $factionId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('factions')
            ->where('id', $factionId)
            ->update([
                'members_count' => new RawSql('members_count + 1'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        $db->transComplete();
    }

    /**
     * Retire un membre + decremente members_count. Atomique, conditionne sur le DELETE
     * qui doit effectivement avoir affecte une ligne (sinon concurrence kick/leave ferait
     * deriver members_count et nullifierait un faction_id qui appartient deja a une autre faction).
     * Ne touche pas au leader : a check par le caller.
     */
    public function removeMember(int $factionId, int $playerId): void
    {
        $db = db_connect();
        $db->transStart();

        // Delete conditionnel : la suite ne se fait que si on a vraiment retire le membre.
        $this->builder()
            ->where('faction_id', $factionId)
            ->where('player_id', $playerId)
            ->delete();
        if ($db->affectedRows() === 0) {
            // Pas de membership a retirer : aucun effet sur players.faction_id ni members_count.
            $db->transComplete();
            return;
        }

        // Clear faction_id du joueur, mais seulement si elle pointe encore vers cette faction.
        model(PlayerModel::class)->builder()
            ->where('id', $playerId)
            ->where('faction_id', $factionId)
            ->update([
                'faction_id' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $db->table('factions')
            ->where('id', $factionId)
            ->where('members_count >', 0)
            ->update([
                'members_count' => new RawSql('members_count - 1'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        $db->transComplete();
    }
}
