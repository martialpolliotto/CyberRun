<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\PlayerModel;

class Profile extends BaseController
{
    public function index()
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'profile');

        return view('profile', [
            'user'     => $user,
            'player'   => $player,
            'xpToNext' => $player['level'] * 100,
            'stats'    => $playerModel->getEffectiveStats((int) $player['id']),
        ]);
    }
}
