<?php

namespace App\Controllers;

use App\Models\CrimeCategoryModel;
use App\Models\PlayerModel;

class Leaderboards extends BaseController
{
    /** Onglets disponibles : level, credits, crime-<slug>. */
    public function index(?string $tab = null)
    {
        $cats = model(CrimeCategoryModel::class)->listAll();

        // Onglets : level, credits, puis un par categorie de crime.
        $tabs = [
            'level'   => 'Niveau',
            'credits' => 'Crédits',
        ];
        foreach ($cats as $c) {
            $tabs['crime-' . $c['slug']] = 'Crime : ' . $c['name'];
        }

        $tab = $tab ?? 'level';
        if (! isset($tabs[$tab])) {
            return redirect()->to('/leaderboards/level');
        }

        // Resolution des donnees du classement.
        $rows = [];
        $metricLabel = '';
        if ($tab === 'level') {
            $rows = model(PlayerModel::class)->topByField('level', 20);
            $metricLabel = 'Niveau';
        } elseif ($tab === 'credits') {
            $rows = model(PlayerModel::class)->topByField('credits', 20);
            $metricLabel = 'Crédits';
        } elseif (str_starts_with($tab, 'crime-')) {
            $slug = substr($tab, 6);
            $cat  = model(CrimeCategoryModel::class)->findBySlug($slug);
            if ($cat === null) {
                return redirect()->to('/leaderboards/level');
            }
            $rows = model(PlayerModel::class)->topByCrimeCategory((int) $cat['id'], 20);
            $metricLabel = 'XP ' . $cat['name'];
        }

        // Ajoute le status public a chaque row.
        $pm = model(PlayerModel::class);
        foreach ($rows as &$r) {
            $r['_status'] = $pm->resolvePublicStatus($r);
        }
        unset($r);

        return view('leaderboards/index', [
            'tabs'         => $tabs,
            'currentTab'   => $tab,
            'rows'         => $rows,
            'metric_label' => $metricLabel,
        ]);
    }
}
