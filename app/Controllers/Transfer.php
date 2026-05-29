<?php

namespace App\Controllers;

use App\Models\PlayerModel;

class Transfer extends BaseController
{
    /** Envoi de credits a un joueur (POST). */
    public function send()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $targetId = (int) $this->request->getPost('target_player_id');
        $amount   = max(0, (int) $this->request->getPost('amount'));

        $r = model(PlayerModel::class)->transferCredits((int) $me['id'], $targetId, $amount);
        return redirect()->back()->with($r['ok'] ? 'message' : 'error', $r['message']);
    }
}
