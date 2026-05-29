<?php

namespace App\Controllers;

use App\Models\BountyModel;
use App\Models\PlayerModel;
use App\Services\ActivityLogger;

class Bounties extends BaseController
{
    /** Liste publique des primes actives. */
    public function index()
    {
        return view('bounties/index', [
            'bounties' => model(BountyModel::class)->listActive(50),
        ]);
    }

    /** Place une prime sur un joueur (POST). */
    public function place()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $targetId = (int) $this->request->getPost('target_player_id');
        $amount   = max(0, (int) $this->request->getPost('amount'));
        $note     = trim((string) $this->request->getPost('message')) ?: null;

        $target = model(PlayerModel::class)->find($targetId);
        if ($target === null) {
            return redirect()->back()->with('error', 'Cible introuvable.');
        }

        $r = model(BountyModel::class)->place((int) $me['id'], $targetId, $amount, $note);
        if ($r['ok']) {
            $targetUsername = $this->resolveUsername($targetId);
            ActivityLogger::log((int) $me['id'], 'eco', 'Log.bounty_placed',
                ['target' => $targetUsername, 'amount' => $amount], $targetId, $r['bounty_id']);
        }
        return redirect()->back()->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    private function resolveUsername(int $playerId): string
    {
        $row = db_connect()->table('players p')
            ->select('users.username')
            ->join('users', 'users.id = p.user_id', 'inner')
            ->where('p.id', $playerId)
            ->get()->getRowArray();
        return (string) ($row['username'] ?? 'inconnu');
    }
}
