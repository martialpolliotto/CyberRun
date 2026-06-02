<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

/**
 * Vue admin de TOUS les activity logs. Filtres : categorie, periode, username
 * (author ou cible). Lecture seule.
 */
class Logs extends BaseController
{
    public function index()
    {
        $category = (string) $this->request->getGet('cat');
        $period   = (string) $this->request->getGet('period');
        $username = trim((string) $this->request->getGet('q'));

        $category = $category !== '' ? $category : null;
        $period   = $period   !== '' ? $period   : null;
        $username = $username !== '' ? $username : null;

        $result = model(ActivityLogModel::class)->listAll($category, $period, $username, 100);

        return view('admin/logs/index', [
            'rows'     => $result['rows'],
            'pager'    => $result['pager'],
            'category' => $category,
            'period'   => $period,
            'username' => $username,
        ]);
    }
}
