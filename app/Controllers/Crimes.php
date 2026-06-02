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

        // Stats effectives une seule fois pour la page (inclut equip + effets actifs - malus addiction).
        $stats = model(PlayerModel::class)->getEffectiveStats((int) $player['id']);

        // Enrichi chaque crime avec : unlocked, success_pct estime, time_bonus_active.
        $crimeModel = model(CrimeModel::class);
        foreach ($crimes as &$c) {
            $c['_unlocked']      = $crimeModel->isUnlockedFor($c, (int) $progress['xp']);
            $c['_success_pct']   = $crimeModel->estimateSuccessPct($c, $category, $player, (int) $progress['xp'], $stats['total']);
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

    /** Action : tente un crime. Supporte HTMX (renvoie un partial) ou requete classique (redirect). */
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
        $isCritical = ($result['outcome'] ?? null) === 'critical';
        $variantFlash = $result['ok']
            ? (($result['outcome'] ?? '') === 'success' ? 'success' : 'danger')
            : 'danger';

        // ---- Reponse HTMX : re-render le partial _list.php avec les valeurs a jour ----
        if ($this->isHtmx()) {
            // Critique : on force un full navigate vers /jail ou /profile (etat global change).
            // On pose le message en flashdata pour que la page destination l'affiche
            // (sinon le joueur arrive en prison sans explication).
            if ($isCritical) {
                $dest = ($result['critical_destination'] === 'hospital') ? '/profile' : '/jail';
                session()->setFlashdata('error', $result['message']);
                return $this->htmxRedirect($dest);
            }

            // Recharge le player + crimes mis a jour (les compteurs et %reussite ont pu bouger).
            $player    = model(PlayerModel::class)->find((int) $player['id']);
            $progress  = model(\App\Models\PlayerCrimeProgressModel::class)
                ->getOrCreate((int) $player['id'], (int) $category['id']);
            $stats     = model(PlayerModel::class)->getEffectiveStats((int) $player['id']);
            $crimeModel = model(CrimeModel::class);
            $crimes    = $crimeModel->listForCategory((int) $category['id']);
            foreach ($crimes as &$c) {
                $c['_unlocked']      = $crimeModel->isUnlockedFor($c, (int) $progress['xp']);
                $c['_success_pct']   = $crimeModel->estimateSuccessPct($c, $category, $player, (int) $progress['xp'], $stats['total']);
                $c['_time_bonus_on'] = $crimeModel->isTimeBonusActive($c);
            }
            unset($c);

            // Rendu : le partial crimes/_list pour la zone HTMX principale + OOB swaps
            // sidebar (jauges + bloc identite credits/niveau/streak) pour refleter
            // la nerve consommee, les credits/xp gagnes, et eventuels level-up dispo.
            $listHtml = view('crimes/_list', [
                'player'                 => $player,
                'crimes'                 => $crimes,
                'flash_variant'          => $variantFlash,
                'flash_message'          => $result['message'],
                'last_attempted_id'      => $crimeId,
                'last_attempted_outcome' => $result['outcome'] ?? null,
            ]);
            return $listHtml . $this->htmxSidebarOOB((int) $player['id']);
        }

        // ---- Fallback non-HTMX : redirect classique ----
        if ($isCritical) {
            $dest = ($result['critical_destination'] === 'hospital') ? '/profile' : '/jail';
            return redirect()->to($dest)->with('error', $result['message']);
        }
        return redirect()->to('/crimes/' . ($category['slug'] ?? ''))
            ->with($variantFlash === 'success' ? 'message' : 'error', $result['message']);
    }
}
