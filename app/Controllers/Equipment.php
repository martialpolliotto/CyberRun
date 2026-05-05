<?php

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\PlayerItemModel;
use App\Models\PlayerModel;

class Equipment extends BaseController
{
    public function index()
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $piModel     = model(PlayerItemModel::class);
        $player      = $playerModel->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        // Donne les starters au player s'il n'a aucun item (idempotent, lazy).
        $piModel->ensureStarterKit((int) $player['id']);

        $inventory = $piModel->findFullInventory((int) $player['id']);

        // Regroupement : equipped[slot] = 1 item, available[slot][] = items disponibles non équipés
        $equipped  = [];
        $available = [];
        foreach ($inventory as $row) {
            $slot = $row['item_slot'];
            if ((int) $row['equipped'] === 1) {
                $equipped[$slot] = $row;
            } else {
                $available[$slot][] = $row;
            }
        }

        return view('equipment', [
            'user'      => $user,
            'player'    => $player,
            'slots'     => ItemModel::SLOTS,
            'equipped'  => $equipped,
            'available' => $available,
            'stats'     => $playerModel->getEffectiveStats((int) $player['id']),
        ]);
    }

    public function equip(int $playerItemId)
    {
        $user    = auth()->user();
        $player  = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerItemModel::class)->equip((int) $player['id'], $playerItemId);
        return redirect()->to('/equipment')->with($result['ok'] ? 'message' : 'error', $result['message']);
    }

    public function unequip(string $slot)
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        if (! isset(ItemModel::SLOTS[$slot])) {
            return redirect()->to('/equipment')->with('error', 'Slot inconnu.');
        }

        $result = model(PlayerItemModel::class)->unequipSlot((int) $player['id'], $slot);
        return redirect()->to('/equipment')->with($result['ok'] ? 'message' : 'error', $result['message']);
    }
}
