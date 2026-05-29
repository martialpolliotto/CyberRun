<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use App\Models\PlayerModel;

class Logs extends BaseController
{
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $category = (string) $this->request->getGet('cat');
        $period   = (string) $this->request->getGet('period');

        // Normalise (vide -> null).
        $category = $category !== '' ? $category : null;
        $period   = $period !== '' ? $period : null;

        $result = model(ActivityLogModel::class)->listForPlayer(
            (int) $player['id'], $category, $period, 50,
        );

        return view('logs/index', [
            'rows'     => $result['rows'],
            'pager'    => $result['pager'],
            'category' => $category,
            'period'   => $period,
        ]);
    }
}
