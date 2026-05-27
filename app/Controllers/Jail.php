<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use CodeIgniter\I18n\Time;

class Jail extends BaseController
{
    /** Page prison : compteur + bouton evasion. Si pas en prison, redirect crimes. */
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        if (empty($player['in_jail_until']) || Time::parse($player['in_jail_until'])->isBefore(Time::now())) {
            return redirect()->to('/crimes')->with('message', 'Tu n\'es plus en prison.');
        }

        $secondsLeft = max(0, Time::parse($player['in_jail_until'])->getTimestamp() - Time::now()->getTimestamp());

        return view('jail/index', [
            'player'         => $player,
            'seconds_left'   => $secondsLeft,
            'escape_cost'    => PlayerModel::ESCAPE_NERVE_COST,
            'escape_penalty' => PlayerModel::ESCAPE_FAIL_PENALTY_MINUTES,
            'escape_pct'     => min(PlayerModel::ESCAPE_MAX_PCT, PlayerModel::ESCAPE_BASE_PCT + (int) $player['stat_reflexes'] / 2),
        ]);
    }

    /** Action : tente une evasion. */
    public function escape()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerModel::class)->attemptEscape((int) $player['id']);

        if (! $result['ok']) {
            return redirect()->to('/jail')->with('error', $result['message']);
        }

        if (! empty($result['escaped'])) {
            return redirect()->to('/crimes')->with('message', $result['message']);
        }
        return redirect()->to('/jail')->with('error', $result['message']);
    }
}
