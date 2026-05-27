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

        // Tous les consommables du joueur (player_items + items joints).
        $consumables = db_connect()->table('player_items pi')
            ->select('pi.*, i.id AS item_id, i.name AS item_name, i.description AS item_description,
                      i.consumable_type, i.cooldown_seconds,
                      i.effect_hp, i.effect_nrg, i.effect_nrv,
                      i.effect_force, i.effect_blindage, i.effect_reflexes, i.effect_hack,
                      i.effect_hp_max, i.effect_nrg_max, i.effect_nrv_max,
                      i.effect_duration_seconds,
                      i.addiction_threshold_increase, i.overdose_chance_pct,
                      i.overdose_hospital_min, i.overdose_hospital_max,
                      i.image_path, i.discontinued')
            ->join('items i', 'i.id = pi.item_id', 'inner')
            ->where('pi.player_id', (int) $player['id'])
            ->where('i.consumable_type IS NOT NULL')
            ->where('i.discontinued', 0)
            ->orderBy('i.consumable_type')
            ->orderBy('i.name')
            ->get()->getResultArray();

        $activeEffects = model(PlayerActiveEffectModel::class)->getActiveForPlayer((int) $player['id']);

        return view('inventory/index', [
            'player'        => $player,
            'consumables'   => $consumables,
            'activeEffects' => $activeEffects,
        ]);
    }

    public function consume(int $playerItemId)
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerModel::class)->consume((int) $player['id'], $playerItemId);

        // Overdose -> on redirige vers profile pour montrer l'etat hopital.
        if (($result['outcome'] ?? null) === 'overdose') {
            return redirect()->to('/profile')->with('error', $result['message']);
        }

        return redirect()->to('/inventory')
            ->with($result['ok'] ? 'message' : 'error', $result['message']);
    }
}
