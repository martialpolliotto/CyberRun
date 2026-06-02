<?php

namespace App\Controllers;

use App\Models\BankDepositModel;
use App\Models\GameSettingModel;
use App\Models\PlayerModel;

class Bank extends BaseController
{
    /** 4 durees disponibles : jours -> cle du setting de taux. */
    private const DURATION_OPTIONS = [
        7  => 'bank_rate_7d_pct',
        14 => 'bank_rate_14d_pct',
        21 => 'bank_rate_21d_pct',
        28 => 'bank_rate_28d_pct',
    ];

    public function index()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $settings = model(GameSettingModel::class);
        $durations = [];
        foreach (self::DURATION_OPTIONS as $days => $key) {
            $durations[] = ['days' => $days, 'pct' => (float) $settings->get($key, 0)];
        }

        return view('bank/index', [
            'me'         => $me,
            'deposits'   => model(BankDepositModel::class)->listForPlayer((int) $me['id']),
            'durations'  => $durations,
            'max_active' => (int) $settings->get('bank_max_active_deposits', 10),
        ]);
    }

    public function deposit()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $amount = max(0, (int) $this->request->getPost('amount'));
        $days   = (int) $this->request->getPost('duration_days');
        if (! isset(self::DURATION_OPTIONS[$days])) {
            return redirect()->to('/bank')->with('error', 'Durée invalide.');
        }
        $pct = (float) model(GameSettingModel::class)->get(self::DURATION_OPTIONS[$days], 0);

        $r = model(BankDepositModel::class)->deposit((int) $me['id'], $amount, $days, $pct);
        return redirect()->to('/bank')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function withdraw(int $depositId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $r = model(BankDepositModel::class)->withdraw($depositId, (int) $me['id']);
        return redirect()->to('/bank')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }
}
