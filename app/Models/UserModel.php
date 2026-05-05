<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected $afterInsert = ['saveEmailIdentity', 'createPlayerOnRegister'];

    /**
     * Crée la fiche player liée au user juste après son inscription.
     * Idempotent : ne re-crée pas si une fiche existe déjà pour ce user_id.
     */
    protected function createPlayerOnRegister(array $data): array
    {
        $userId = $data['id'] ?? ($data['data']['id'] ?? null);

        if ($userId === null) {
            return $data;
        }

        $playerModel = model(PlayerModel::class);

        if ($playerModel->where('user_id', $userId)->first() === null) {
            $playerModel->insert(['user_id' => $userId]);
        }

        return $data;
    }
}
