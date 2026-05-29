<?php

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\MissionModel;
use App\Models\PlayerActiveEffectModel;
use App\Models\PlayerItemModel;
use App\Models\PlayerModel;

class Inventory extends BaseController
{
    public function index()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId((int) $user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        // Decay lazy de l'addiction a chaque visite.
        model(PlayerModel::class)->decayAddiction((int) $player['id']);
        $player = model(PlayerModel::class)->find((int) $player['id']);

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'inventory');

        // Tous les items du joueur (joins items pour les metadata).
        $rows = db_connect()->table('player_items pi')
            ->select('pi.id AS pi_id, pi.equipped, pi.quantity,
                      i.id AS item_id, i.slug, i.name, i.description, i.slot, i.discontinued,
                      i.bonus_force, i.bonus_blindage, i.bonus_reflexes, i.bonus_hack,
                      i.consumable_type, i.cooldown_seconds,
                      i.effect_hp, i.effect_nrg, i.effect_nrv,
                      i.effect_force, i.effect_blindage, i.effect_reflexes, i.effect_hack,
                      i.effect_hp_max, i.effect_nrg_max, i.effect_nrv_max,
                      i.effect_duration_seconds,
                      i.addiction_threshold_increase, i.overdose_chance_pct,
                      i.overdose_hospital_min, i.overdose_hospital_max,
                      i.image_path, i.model_path')
            ->join('items i', 'i.id = pi.item_id', 'inner')
            ->where('pi.player_id', (int) $player['id'])
            ->orderBy('pi.equipped', 'DESC')
            ->orderBy('i.name')
            ->get()->getResultArray();

        // Resolve categorie + filtre.
        $filter = (string) $this->request->getGet('cat');
        if (! isset(ItemModel::CATEGORIES[$filter])) $filter = 'all';

        foreach ($rows as &$r) {
            $r['_category'] = ItemModel::resolveCategory($r);
        }
        unset($r);

        $filtered = match ($filter) {
            'equipped'  => array_values(array_filter($rows, static fn($r) => (int) $r['equipped'] === 1)),
            'available' => array_values(array_filter($rows, static fn($r) => (int) $r['equipped'] !== 1 && empty($r['consumable_type']))),
            'all'       => $rows,
            default     => array_values(array_filter($rows, static fn($r) => $r['_category'] === $filter)),
        };

        $activeEffects = model(PlayerActiveEffectModel::class)->getActiveForPlayer((int) $player['id']);

        return view('inventory/index', [
            'player'        => $player,
            'rows'          => $filtered,
            'totalCount'    => count($rows),
            'activeEffects' => $activeEffects,
            'filter'        => $filter,
        ]);
    }

    public function consume(int $playerItemId)
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerModel::class)->consume((int) $player['id'], $playerItemId);

        if (($result['outcome'] ?? null) === 'overdose') {
            return redirect()->to('/profile')->with('error', $result['message']);
        }

        return redirect()->to('/inventory')
            ->with($result['ok'] ? 'message' : 'error', $result['message']);
    }
}
