<?php

namespace App\Controllers;

use App\Models\CombatModel;
use App\Models\CombatTurnModel;
use App\Models\PlayerModel;
use App\Services\CombatService;

class Combat extends BaseController
{
    /** Démarre un combat contre la cible et redirige sur la page combat. */
    public function start(int $targetPlayerId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $r = (new CombatService())->initiate((int) $me['id'], $targetPlayerId);
        if (! $r['ok']) {
            $username = $this->resolveUsername($targetPlayerId);
            return redirect()->to('/u/' . $username)->with('error', $r['message']);
        }
        return redirect()->to('/combat/' . (int) $r['combat_id']);
    }

    /** Page d'un combat. */
    public function view(int $combatId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $combat = model(CombatModel::class)->find($combatId);
        if ($combat === null) {
            return redirect()->to('/players')->with('error', 'Combat introuvable.');
        }
        $myId = (int) $me['id'];
        $isAttacker = (int) $combat['attacker_player_id'] === $myId;
        $isDefender = (int) $combat['defender_player_id'] === $myId;
        if (! $isAttacker && ! $isDefender) {
            return redirect()->to('/players')->with('error', 'Tu ne participes pas a ce combat.');
        }

        $opponentId = $isAttacker ? (int) $combat['defender_player_id'] : (int) $combat['attacker_player_id'];

        $turns = model(CombatTurnModel::class)->listForCombat($combatId);

        return view('combat/view', [
            'combat'         => $combat,
            'turns'          => $turns,
            'me'             => $me,
            'me_username'    => auth()->user()->username,
            'opponent_username' => $this->resolveUsername($opponentId),
            'opponent_id'    => $opponentId,
            'is_attacker'    => $isAttacker,
        ]);
    }

    /** Joue un tour. */
    public function turn(int $combatId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $action = (string) $this->request->getPost('action');
        $r = (new CombatService())->takeTurn($combatId, (int) $me['id'], $action);
        if (! $r['ok']) {
            return redirect()->to('/combat/' . $combatId)->with('error', $r['message']);
        }
        return redirect()->to('/combat/' . $combatId);
    }

    /** Post-action vainqueur : mug / hospitalize / leave. */
    public function postAction(int $combatId, string $action)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $r = (new CombatService())->postAction($combatId, (int) $me['id'], $action);
        return redirect()->to('/combat/' . $combatId)->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

}
