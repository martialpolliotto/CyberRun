<?php

namespace App\Controllers;

use App\Models\CrimeCategoryModel;
use App\Models\CrimeModel;
use App\Models\MissionModel;
use App\Models\PlayerCrimeProgressModel;
use App\Models\PlayerModel;
use CodeIgniter\I18n\Time;

class Crimes extends BaseController
{
    /** Liste des categories avec leur progression XP pour ce joueur. */
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        // Redirect prison/hopital si en cours.
        if (! empty($player['in_jail_until']) && Time::parse($player['in_jail_until'])->isAfter(Time::now())) {
            return redirect()->to('/jail');
        }

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'crimes');

        $categories  = model(CrimeCategoryModel::class)->listAll();
        $progressMap = model(PlayerCrimeProgressModel::class)->findAllForPlayerIndexed((int) $player['id']);

        return view('crimes/index', [
            'player'      => $player,
            'categories'  => $categories,
            'progressMap' => $progressMap,
        ]);
    }

    /** Page d'une categorie : liste les crimes debloques + verrouilles avec leurs stats. */
    public function show(string $slug)
    {
        $category = model(CrimeCategoryModel::class)->findBySlug($slug);
        if ($category === null) {
            return redirect()->to('/crimes')->with('error', 'Categorie introuvable.');
        }

        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        if (! empty($player['in_jail_until']) && Time::parse($player['in_jail_until'])->isAfter(Time::now())) {
            return redirect()->to('/jail');
        }

        $progress = model(PlayerCrimeProgressModel::class)->getOrCreate((int) $player['id'], (int) $category['id']);
        $crimes   = model(CrimeModel::class)->listForCategory((int) $category['id']);

        // Enrichi chaque crime avec : unlocked, success_pct estime, time_bonus_active.
        $crimeModel = model(CrimeModel::class);
        foreach ($crimes as &$c) {
            $c['_unlocked']      = $crimeModel->isUnlockedFor($c, (int) $progress['xp']);
            $c['_success_pct']   = $crimeModel->estimateSuccessPct($c, $category, $player, (int) $progress['xp']);
            $c['_time_bonus_on'] = $crimeModel->isTimeBonusActive($c);
        }
        unset($c);

        return view('crimes/show', [
            'player'   => $player,
            'category' => $category,
            'progress' => $progress,
            'crimes'   => $crimes,
        ]);
    }

    /** Action : tente un crime. */
    public function attempt(int $crimeId)
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $crime = model(CrimeModel::class)->find($crimeId);
        if ($crime === null) {
            return redirect()->to('/crimes')->with('error', 'Crime introuvable.');
        }
        $category = model(CrimeCategoryModel::class)->find((int) $crime['category_id']);

        $result = model(CrimeModel::class)->attempt((int) $player['id'], $crimeId);

        $variant = $result['ok'] ? ($result['outcome'] === 'success' ? 'message' : 'error') : 'error';
        // Si critical -> redirect direct sur jail ou hospital.
        if (($result['outcome'] ?? null) === 'critical') {
            $dest = ($result['critical_destination'] === 'hospital') ? '/profile' : '/jail';
            return redirect()->to($dest)->with('error', $result['message']);
        }

        return redirect()->to('/crimes/' . ($category['slug'] ?? ''))
            ->with($variant, $result['message']);
    }
}
