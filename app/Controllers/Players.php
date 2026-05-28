<?php

namespace App\Controllers;

use App\Models\PlayerModel;

class Players extends BaseController
{
    /** Tente un bust sur le detenu cible. Le filter 'free' garantit que l'attaquant est libre. */
    public function bust(int $targetPlayerId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerModel::class)->bust((int) $me['id'], $targetPlayerId);

        // En cas d'echec, l'attaquant est lui-meme parti en prison.
        if (($result['outcome'] ?? null) === 'fail') {
            return redirect()->to('/jail')->with('error', $result['message']);
        }

        return redirect()->to('/players/jail')->with($result['ok'] ? 'message' : 'error', $result['message']);
    }

    /** Paie la caution d'un detenu : sortie immediate de la cible. */
    public function bail(int $targetPlayerId)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = model(PlayerModel::class)->payBail((int) $me['id'], $targetPlayerId);

        return redirect()->to('/players/jail')->with($result['ok'] ? 'message' : 'error', $result['message']);
    }

    /** Liste des joueurs avec recherche + filtre par status (null|jail|hospital). */
    public function index(?string $status = null)
    {
        $validStatuses = [null, 'jail', 'hospital'];
        if (! in_array($status, $validStatuses, true)) {
            return redirect()->to('/players');
        }

        $query  = trim((string) $this->request->getGet('q'));
        $result = model(PlayerModel::class)->searchByUsername($query, 30, $status);

        // Sur l'onglet "jail", calcule pour chaque ligne le cout bail + % bust estime, pour les boutons.
        $me = function_exists('auth') && auth()->loggedIn()
            ? model(PlayerModel::class)->findByUserId((int) auth()->user()->id)
            : null;

        if ($status === 'jail' && $me !== null) {
            $pm = model(PlayerModel::class);
            foreach ($result['rows'] as &$r) {
                $target = $pm->find((int) $r['id']);
                $r['_bail_cost']  = $pm->calculateBailCost($target);
                $r['_bust_pct']   = $pm->estimateBustPct($me, $target);
            }
            unset($r);
        }

        return view('players/index', [
            'rows'   => $result['rows'],
            'pager'  => $result['pager'],
            'query'  => $query,
            'status' => $status,
            'me'     => $me,
        ]);
    }

    /** Profil public d'un joueur via son username. */
    public function show(string $username)
    {
        $pm     = model(PlayerModel::class);
        $player = $pm->findByUsername($username);
        if ($player === null) {
            return redirect()->to('/players')->with('error', 'Joueur introuvable.');
        }

        $me = function_exists('auth') && auth()->loggedIn()
            ? $pm->findByUserId((int) auth()->user()->id)
            : null;

        // Si la cible est en prison, calcule cout bail + % bust pour les boutons.
        if ($player['_status'] === 'jail' && $me !== null) {
            $target            = $pm->find((int) $player['id']);
            $player['_bail_cost'] = $pm->calculateBailCost($target);
            $player['_bust_pct']  = $pm->estimateBustPct($me, $target);
        }

        return view('players/show', [
            'profile' => $player,
            'me'      => $me,
        ]);
    }
}
