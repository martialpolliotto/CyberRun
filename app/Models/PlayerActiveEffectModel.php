<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class PlayerActiveEffectModel extends Model
{
    protected $table         = 'player_active_effects';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'player_id', 'kind', 'item_id', 'started_at', 'expires_at',
    ];

    /**
     * Liste les effets actifs (non expires) du joueur, joints aux items pour avoir les bonus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveForPlayer(int $playerId): array
    {
        $now = Time::now()->toDateTimeString();
        return $this->select('player_active_effects.*, items.name AS item_name, items.consumable_type, items.effect_force, items.effect_blindage, items.effect_reflexes, items.effect_hack, items.effect_hp_max, items.effect_nrg_max, items.effect_nrv_max')
            ->join('items', 'items.id = player_active_effects.item_id', 'inner')
            ->where('player_active_effects.player_id', $playerId)
            ->where('player_active_effects.expires_at >', $now)
            ->orderBy('player_active_effects.expires_at')
            ->findAll();
    }

    /**
     * Aggrege les bonus actifs (somme des stats / stats max sur tous les effets non expires).
     *
     * @return array{
     *   force:int, blindage:int, reflexes:int, hack:int,
     *   hp_max:int, nrg_max:int, nrv_max:int
     * }
     */
    public function aggregateBonuses(int $playerId): array
    {
        $bonuses = [
            'force' => 0, 'blindage' => 0, 'reflexes' => 0, 'hack' => 0,
            'hp_max' => 0, 'nrg_max' => 0, 'nrv_max' => 0,
        ];
        foreach ($this->getActiveForPlayer($playerId) as $row) {
            $bonuses['force']    += (int) ($row['effect_force']    ?? 0);
            $bonuses['blindage'] += (int) ($row['effect_blindage'] ?? 0);
            $bonuses['reflexes'] += (int) ($row['effect_reflexes'] ?? 0);
            $bonuses['hack']     += (int) ($row['effect_hack']     ?? 0);
            $bonuses['hp_max']   += (int) ($row['effect_hp_max']   ?? 0);
            $bonuses['nrg_max']  += (int) ($row['effect_nrg_max']  ?? 0);
            $bonuses['nrv_max']  += (int) ($row['effect_nrv_max']  ?? 0);
        }
        return $bonuses;
    }

    /**
     * Installe ou remplace l'effet du player pour ce kind ('booster'|'drug').
     * Si un effet de ce kind existe deja (meme expire), il est remplace.
     */
    public function setOrReplace(int $playerId, string $kind, int $itemId, Time $expiresAt): void
    {
        $existing = $this->where('player_id', $playerId)->where('kind', $kind)->first();
        $now = Time::now();
        $data = [
            'player_id'  => $playerId,
            'kind'       => $kind,
            'item_id'    => $itemId,
            'started_at' => $now->toDateTimeString(),
            'expires_at' => $expiresAt->toDateTimeString(),
        ];
        if ($existing === null) {
            $this->insert($data);
        } else {
            $this->update($existing['id'], $data);
        }
    }

    /** Indique si un effet du kind donne est actuellement actif (non expire). */
    public function hasActive(int $playerId, string $kind): bool
    {
        $row = $this->where('player_id', $playerId)->where('kind', $kind)->first();
        if ($row === null) return false;
        return Time::parse($row['expires_at'])->isAfter(Time::now());
    }
}
