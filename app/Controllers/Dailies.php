<?php

namespace App\Controllers;

use App\Services\DailyService;

class Dailies extends BaseController
{
    public function index()
    {
        $me = $this->requireMe();
        $service = new DailyService();

        return view('dailies/index', [
            'me'      => $me,
            'dailies' => $service->listForPlayer((int) $me['id']),
        ]);
    }

    public function claim(int $assignmentId)
    {
        $me = $this->requireMe();
        $r  = (new DailyService())->claim((int) $me['id'], $assignmentId);
        return redirect()->to('/dailies')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }
}
