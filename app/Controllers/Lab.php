<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\PlayerModel;

class Lab extends BaseController
{
    public function index()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'lab');

        return view('lab', [
            'user'            => $user,
            'player'          => $player,
            'trainEnergyCost' => PlayerModel::TRAIN_ENERGY_COST,
            'trainStatGain'   => PlayerModel::TRAIN_STAT_GAIN,
            'trainableStats'  => PlayerModel::TRAINABLE_STATS,
        ]);
    }

    public function train(string $statSlug)
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $result = $playerModel->train((int) $player['id'], $statSlug);
        // Note : PlayerModel::train pose deja le trackEvent + recheckThresholds (humains + bots).

        // ---- Reponse HTMX : re-render le partial _content.php avec les valeurs a jour ----
        if ($this->isHtmx()) {
            $player = $playerModel->find((int) $player['id']);
            $cost   = PlayerModel::TRAIN_ENERGY_COST;
            $gain   = PlayerModel::TRAIN_STAT_GAIN;
            $canTrain = (int) $player['energy_current'] >= $cost
                && (empty($player['in_hospital_until'])
                    || \CodeIgniter\I18n\Time::parse($player['in_hospital_until'])->isBefore(\CodeIgniter\I18n\Time::now()));

            $contentHtml = view('lab/_content', [
                'player'      => $player,
                'cost'        => $cost,
                'gain'        => $gain,
                'statLabels'  => [
                    'force'    => 'Force',
                    'blindage' => 'Blindage',
                    'reflexes' => 'Réflexes',
                    'hack'     => 'Hack',
                ],
                'statColumns' => [
                    'force'    => 'stat_force',
                    'blindage' => 'stat_blindage',
                    'reflexes' => 'stat_reflexes',
                    'hack'     => 'stat_hack',
                ],
                'canTrain'      => $canTrain,
                'flash_variant' => $result['ok'] ? 'success' : 'danger',
                'flash_message' => $result['message'],
            ]);

            // OOB swap : jauges + identite (solde / niveau / streak).
            return $contentHtml . $this->htmxSidebarOOB((int) $player['id']);
        }

        return redirect()->to('/lab')->with(
            $result['ok'] ? 'message' : 'error',
            $result['message'],
        );
    }
}
