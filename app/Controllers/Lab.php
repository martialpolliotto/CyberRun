<?php

namespace App\Controllers;

use App\Models\PlayerModel;

class Lab extends BaseController
{
    public function index()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        return view('lab', [
            'user'            => $user,
            'player'          => $player,
            'trainEnergyCost' => PlayerModel::TRAIN_ENERGY_COST,
            'trainStatGain'   => PlayerModel::TRAIN_STAT_GAIN,
            'trainableStats'  => PlayerModel::TRAINABLE_STATS,
        ]);
    }

    public function train(string $statSlug)
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = $playerModel->train((int) $player['id'], $statSlug);

        return redirect()->to('/lab')->with(
            $result['ok'] ? 'message' : 'error',
            $result['message'],
        );
    }
}
