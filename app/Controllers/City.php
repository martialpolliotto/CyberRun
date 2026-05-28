<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\PlayerModel;
use App\Models\VendorModel;
use CodeIgniter\I18n\Time;

class City extends BaseController
{
    /** Hub central : grille des lieux de Chrome City. */
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'city');

        $nowStr = Time::now()->toDateTimeString();
        $db     = db_connect();

        // Compteurs en temps reel des lieux dynamiques.
        $inmateCount = $db->table('players')->where('in_jail_until >', $nowStr)->countAllResults();
        $patientCount = $db->table('players')->where('in_hospital_until >', $nowStr)->countAllResults();

        $vendors = model(VendorModel::class)->listAll();

        return view('city/index', [
            'player'        => $player,
            'inmateCount'   => $inmateCount,
            'patientCount'  => $patientCount,
            'vendors'       => $vendors,
        ]);
    }
}
