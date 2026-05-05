<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ItemModel;
use App\Models\PlayerModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $stats = [
            'items_total'       => model(ItemModel::class)->countAllResults(false),
            'items_active'      => model(ItemModel::class)->where('discontinued', 0)->countAllResults(),
            'items_discontinued' => model(ItemModel::class)->where('discontinued', 1)->countAllResults(),
            'players_total'     => model(PlayerModel::class)->countAllResults(),
        ];

        return view('admin/dashboard', ['stats' => $stats]);
    }
}
