<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use App\Services\AchievementService;

class Achievements extends BaseController
{
    public function index()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $service = new AchievementService();
        return view('achievements/index', [
            'me'      => $me,
            'grouped' => $service->listForPlayerGrouped((int) $me['id']),
            'counts'  => $service->counts((int) $me['id']),
        ]);
    }
}
