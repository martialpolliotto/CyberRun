<?php

namespace App\Controllers;

use App\Models\FixerModel;
use App\Models\MissionModel;
use App\Models\PlayerModel;

class Fixers extends BaseController
{
    /** Liste des fixers debloques pour le player connecte. */
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $fixers = model(FixerModel::class)->listUnlocked((int) $player['id']);

        // Pour chaque fixer, on indique s'il a une mission active (badge "!").
        $missionModel = model(MissionModel::class);
        foreach ($fixers as &$f) {
            $current = $missionModel->getCurrentMissionForPlayer((int) $f['id'], (int) $player['id']);
            if ($current === null) {
                $f['_status'] = 'done';
            } elseif ($current['player_status'] === null) {
                $f['_status'] = 'new';
            } elseif ($current['player_status'] === 'completed') {
                $f['_status'] = 'claimable';
            } else {
                $f['_status'] = 'in_progress';
            }
        }
        unset($f);

        return view('fixers/index', ['fixers' => $fixers]);
    }

    public function show(string $slug)
    {
        $fixer = model(FixerModel::class)->findBySlug($slug);
        if ($fixer === null) {
            return redirect()->to('/fixers')->with('error', 'Fixer introuvable.');
        }

        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        // Verifie que ce fixer est debloque pour ce player.
        $unlocked = model(FixerModel::class)->listUnlocked((int) $player['id']);
        $isUnlocked = false;
        foreach ($unlocked as $u) {
            if ((int) $u['id'] === (int) $fixer['id']) { $isUnlocked = true; break; }
        }
        if (! $isUnlocked) {
            return redirect()->to('/fixers')->with('error', 'Ce fixer n\'est pas encore debloque.');
        }

        // Re-evalue les seuils avant d'afficher (utile si player a level-up ailleurs).
        model(MissionModel::class)->recheckThresholdsForPlayer((int) $player['id']);

        $current = model(MissionModel::class)->getCurrentMissionForPlayer((int) $fixer['id'], (int) $player['id']);

        // Historique des missions deja claimed chez ce fixer (pour rappel).
        $claimed = model(MissionModel::class)
            ->select('missions.*, pm.claimed_at')
            ->join('player_missions pm', 'pm.mission_id = missions.id AND pm.player_id = ' . (int) $player['id'], 'inner')
            ->where('missions.fixer_id', (int) $fixer['id'])
            ->where('pm.status', 'claimed')
            ->orderBy('missions.mission_order')
            ->findAll();

        return view('fixers/show', [
            'fixer'   => $fixer,
            'current' => $current,
            'claimed' => $claimed,
            'player'  => $player,
        ]);
    }

    public function accept(int $missionId)
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $mission = model(MissionModel::class)->find($missionId);
        if ($mission === null) {
            return redirect()->to('/fixers')->with('error', 'Mission introuvable.');
        }

        $result = model(MissionModel::class)->accept((int) $player['id'], $missionId);

        $fixer = model(FixerModel::class)->find((int) $mission['fixer_id']);
        return redirect()->to('/fixers/' . ($fixer['slug'] ?? ''))
            ->with($result['ok'] ? 'message' : 'error', $result['message']);
    }

    public function claim(int $missionId)
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $mission = model(MissionModel::class)->find($missionId);
        if ($mission === null) {
            return redirect()->to('/fixers')->with('error', 'Mission introuvable.');
        }

        $result = model(MissionModel::class)->claim((int) $player['id'], $missionId);

        $fixer = model(FixerModel::class)->find((int) $mission['fixer_id']);
        return redirect()->to('/fixers/' . ($fixer['slug'] ?? ''))
            ->with($result['ok'] ? 'message' : 'error', $result['message']);
    }
}
