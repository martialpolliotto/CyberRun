<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatModel extends Model
{
    protected $table         = 'combats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'attacker_player_id', 'defender_player_id',
        'status',
        'attacker_hp_remaining', 'defender_hp_remaining',
        'attacker_hp_initial', 'defender_hp_initial',
        'current_turn_player_id',
        'winner_player_id',
        'post_action', 'mug_amount',
        'ended_at',
    ];

    public function findOngoingForPlayer(int $playerId): ?array
    {
        return $this->where('status', 'ongoing')
            ->groupStart()
                ->where('attacker_player_id', $playerId)
                ->orWhere('defender_player_id', $playerId)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Combats finis ou j'etais defenseur, par ordre antechronologique.
     * Sert a la card 'Tes derniers attaquants' sur le profil = revenge list.
     *
     * @return array<int, array{
     *   id:int, ended_at:string, status:string, post_action:?string, mug_amount:int,
     *   attacker_player_id:int, attacker_username:string
     * }>
     */
    public function recentAttacksOn(int $playerId, int $limit = 10): array
    {
        return $this->select('combats.id, combats.ended_at, combats.status,
                              combats.post_action, combats.mug_amount, combats.attacker_player_id,
                              users.username AS attacker_username')
            ->join('players',  'players.id = combats.attacker_player_id', 'inner')
            ->join('users',    'users.id = players.user_id', 'inner')
            ->where('combats.defender_player_id', $playerId)
            ->where('combats.status !=', 'ongoing')
            ->orderBy('combats.ended_at', 'DESC')
            ->orderBy('combats.id', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
