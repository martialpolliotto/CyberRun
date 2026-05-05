<?php

namespace App\Controllers;

use App\Models\PlayerModel;

class Profile extends BaseController
{
    public function index()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        return view('profile', [
            'user'     => $user,
            'player'   => $player,
            'xpToNext' => $player['level'] * 100,
        ]);
    }
}
