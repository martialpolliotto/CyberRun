<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlayerModel;
use App\Models\UserModel;
use App\Services\BotService;

class Bots extends BaseController
{
    public function index()
    {
        $bots = model(PlayerModel::class)
            ->select('players.*, users.username, users.created_at AS joined_at')
            ->join('users', 'users.id = players.user_id', 'inner')
            ->where('players.is_bot', 1)
            ->orderBy('players.bot_persona')
            ->orderBy('users.username')
            ->findAll();

        // Compte par persona pour stats.
        $byPersona = [];
        foreach ($bots as $b) {
            $p = (string) $b['bot_persona'];
            $byPersona[$p] = ($byPersona[$p] ?? 0) + 1;
        }

        return view('admin/bots/index', [
            'bots'      => $bots,
            'personas'  => array_keys(BotService::PERSONAS),
            'byPersona' => $byPersona,
        ]);
    }

    /** Cree N bots de la persona donnee. */
    public function populate()
    {
        $count   = max(1, min(50, (int) $this->request->getPost('count')));
        $persona = (string) $this->request->getPost('persona');

        if (! isset(BotService::PERSONAS[$persona])) {
            return redirect()->to('/admin/bots')->with('error', 'Persona inconnue.');
        }

        $r = (new BotService())->populate($count, $persona);
        $msg = $r['created'] . ' bots ' . esc($persona) . ' crees.';
        if (! empty($r['errors'])) {
            $msg .= ' Avec ' . count($r['errors']) . ' erreur(s).';
        }
        return redirect()->to('/admin/bots')->with('message', $msg);
    }

    /** Supprime un bot (et son user lie, et toutes ses donnees via cascade). */
    public function destroy(int $id)
    {
        $player = model(PlayerModel::class)->find($id);
        if ($player === null || (int) ($player['is_bot'] ?? 0) !== 1) {
            return redirect()->to('/admin/bots')->with('error', 'Bot introuvable.');
        }
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->to('/admin/bots')->with('error', 'Confirmation requise.');
        }
        // Suppression du user cascade sur players via FK.
        model(UserModel::class)->delete((int) $player['user_id'], true);
        return redirect()->to('/admin/bots')->with('message', 'Bot supprime.');
    }

    /** Supprime tous les bots d'une persona (ou tous si persona='*'). */
    public function destroyAll()
    {
        $persona = (string) $this->request->getPost('persona');
        if (! $this->request->getPost('confirm_delete_all')) {
            return redirect()->to('/admin/bots')->with('error', 'Confirmation requise.');
        }

        $q = model(PlayerModel::class)->where('is_bot', 1);
        if ($persona !== '*') {
            $q = $q->where('bot_persona', $persona);
        }
        $bots = $q->findAll();
        $count = 0;
        foreach ($bots as $b) {
            model(UserModel::class)->delete((int) $b['user_id'], true);
            $count++;
        }
        return redirect()->to('/admin/bots')->with('message', $count . ' bots supprimes.');
    }
}
