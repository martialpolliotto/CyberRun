<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use App\Models\PlayerRelationModel;
use App\Services\ActivityLogger;

class Relations extends BaseController
{
    /** Liste 3-onglets de mes relations avec statut online/jail/hospital. */
    public function index()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $threshold = (int) model(\App\Models\GameSettingModel::class)->get('online_threshold_seconds', 300);
        return view('relations/index', [
            'me'         => $me,
            'grouped'    => model(PlayerRelationModel::class)->listForPlayerGrouped((int) $me['id'], $threshold),
            'threshold'  => $threshold,
        ]);
    }

    /** Toggle ami/ennemi/cible sur un joueur. */
    public function toggle(string $type, int $targetPlayerId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $target = model(PlayerModel::class)->find($targetPlayerId);
        if ($target === null) {
            return redirect()->to('/players')->with('error', 'Joueur introuvable.');
        }

        $result = model(PlayerRelationModel::class)->toggle((int) $me['id'], $targetPlayerId, $type);
        if ($result === 'invalid') {
            return redirect()->back()->with('error', 'Action impossible.');
        }

        $targetUsername = $this->resolveUsername($targetPlayerId);
        $msg = $result === 'added'
            ? 'Ajouté à tes ' . PlayerRelationModel::RELATION_TYPES[$type] . 's : ' . esc($targetUsername)
            : 'Retiré de tes ' . PlayerRelationModel::RELATION_TYPES[$type] . 's : ' . esc($targetUsername);

        ActivityLogger::log((int) $me['id'], 'social', 'Log.relation_' . $result, [
            'target' => $targetUsername,
            'type'   => PlayerRelationModel::RELATION_TYPES[$type] ?? $type,
        ], $targetPlayerId);

        return redirect()->to('/u/' . $targetUsername)->with('message', $msg);
    }

}
