<?php

namespace App\Controllers;

use App\Models\JobModel;
use App\Models\JobPositionModel;
use App\Models\PlayerModel;

class Jobs extends BaseController
{
    /** Catalogue : 7 jobs avec leur description et entree de palier. */
    public function index()
    {
        $jobs = model(JobModel::class)->listAll();
        $me   = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);

        // Pour chaque job, on annexe le 1er salaire (rank 1) pour donner un ordre de grandeur.
        foreach ($jobs as &$j) {
            $rank1 = model(JobPositionModel::class)->where('job_id', $j['id'])->where('rank', 1)->first();
            $j['_starting_salary'] = $rank1 !== null ? (int) $rank1['daily_salary'] : 0;
        }
        unset($j);

        return view('jobs/index', [
            'jobs' => $jobs,
            'me'   => $me,
        ]);
    }

    /** Detail d'un job : description + 7 positions + perks + boutons selon etat. */
    public function show(string $slug)
    {
        $job = model(JobModel::class)->findBySlug($slug);
        if ($job === null) {
            return redirect()->to('/jobs')->with('error', 'Job introuvable.');
        }

        $positions = model(JobPositionModel::class)->listForJob((int) $job['id']);
        $me        = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        $myCurrent = null;
        if ($me !== null && ! empty($me['job_position_id'])) {
            $myCurrent = model(JobPositionModel::class)->find((int) $me['job_position_id']);
        }

        return view('jobs/show', [
            'job'        => $job,
            'positions'  => $positions,
            'me'         => $me,
            'my_current' => $myCurrent,
        ]);
    }

    public function apply(string $slug)
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $r = model(PlayerModel::class)->applyToJob((int) $me['id'], $slug);
        return redirect()->to('/jobs/' . $slug)->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function quit()
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        $r = model(PlayerModel::class)->quitJob((int) $me['id']);
        return redirect()->to('/jobs')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

}
