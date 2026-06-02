<?php

namespace App\Controllers;

use App\Models\GameSettingModel;
use App\Models\PlayerModel;

class LevelUp extends BaseController
{
    public function index()
    {
        $me = $this->requireMe();
        $playerModel = model(PlayerModel::class);
        $bonus       = (int) model(GameSettingModel::class)->get('level_up_hp_max_bonus', 10);

        $threshold = (int) $me['level'] * 100;
        $canLevel  = (int) $me['xp'] >= $threshold;

        return view('level_up/index', [
            'me'        => $me,
            'threshold' => $threshold,
            'can_level' => $canLevel,
            'bonus'     => $bonus,
        ]);
    }

    public function perform()
    {
        $me = $this->requireMe();
        $r  = model(PlayerModel::class)->levelUp((int) $me['id']);
        return redirect()->to('/level-up')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }
}
