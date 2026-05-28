<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GameSettingModel;

class GameSettings extends BaseController
{
    public function index()
    {
        return view('admin/game_settings/index', [
            'grouped' => model(GameSettingModel::class)->listGrouped(),
        ]);
    }

    /** Sauvegarde toutes les valeurs en bulk depuis le formulaire (input name="values[key]"). */
    public function save()
    {
        $values = $this->request->getPost('values') ?? [];
        if (! is_array($values)) {
            return redirect()->to('/admin/game-settings')->with('error', 'Payload invalide.');
        }

        $model = model(GameSettingModel::class);
        $count = 0;
        foreach ($values as $key => $raw) {
            if (! is_string($key)) continue;
            $model->setValue($key, trim((string) $raw));
            $count++;
        }
        return redirect()->to('/admin/game-settings')->with('message', $count . ' parametre(s) sauvegarde(s).');
    }
}
