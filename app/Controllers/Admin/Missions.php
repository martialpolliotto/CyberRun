<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FixerModel;
use App\Models\ItemModel;
use App\Models\MissionModel;

class Missions extends BaseController
{
    public function index()
    {
        $missions = model(MissionModel::class)
            ->select('missions.*, fixers.name AS fixer_name, fixers.slug AS fixer_slug')
            ->join('fixers', 'fixers.id = missions.fixer_id', 'inner')
            ->orderBy('fixers.unlock_order')
            ->orderBy('missions.mission_order')
            ->findAll();

        return view('admin/missions/index', ['missions' => $missions]);
    }

    public function new()
    {
        return view('admin/missions/form', [
            'mission' => null,
            'fixers'  => model(FixerModel::class)->listAll(),
            'items'   => model(ItemModel::class)->orderBy('name')->findAll(),
            'types'   => MissionModel::OBJECTIVE_TYPES,
        ]);
    }

    public function edit(int $id)
    {
        $mission = model(MissionModel::class)->find($id);
        if ($mission === null) {
            return redirect()->to('/admin/missions')->with('error', 'Mission introuvable.');
        }
        return view('admin/missions/form', [
            'mission' => $mission,
            'fixers'  => model(FixerModel::class)->listAll(),
            'items'   => model(ItemModel::class)->orderBy('name')->findAll(),
            'types'   => MissionModel::OBJECTIVE_TYPES,
        ]);
    }

    public function save(?int $id = null)
    {
        $model = model(MissionModel::class);
        $existing = $id ? $model->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to('/admin/missions')->with('error', 'Mission introuvable.');
        }

        $rewardItemId = $this->request->getPost('reward_item_id');
        $data = [
            'fixer_id'         => (int) $this->request->getPost('fixer_id'),
            'slug'             => trim($this->request->getPost('slug') ?? ''),
            'name'             => trim($this->request->getPost('name') ?? ''),
            'brief'            => trim($this->request->getPost('brief') ?? '') ?: null,
            'outro'            => trim($this->request->getPost('outro') ?? '') ?: null,
            'mission_order'    => max(1, (int) $this->request->getPost('mission_order')),
            'objective_type'   => (string) $this->request->getPost('objective_type'),
            'objective_target' => trim($this->request->getPost('objective_target') ?? '') ?: '*',
            'objective_count'  => max(1, (int) $this->request->getPost('objective_count')),
            'reward_credits'   => max(0, (int) $this->request->getPost('reward_credits')),
            'reward_xp'        => max(0, (int) $this->request->getPost('reward_xp')),
            'reward_item_id'   => ($rewardItemId !== '' && $rewardItemId !== null) ? (int) $rewardItemId : null,
        ];

        if ($data['slug'] === '' || $data['name'] === '' || $data['fixer_id'] <= 0
            || ! isset(MissionModel::OBJECTIVE_TYPES[$data['objective_type']])
        ) {
            return redirect()->back()->withInput()->with('error', 'Slug, nom, fixer et type d\'objectif sont obligatoires.');
        }
        $slugCheck = $model->where('slug', $data['slug']);
        if ($id !== null) {
            $slugCheck->where('id !=', $id);
        }
        if ($slugCheck->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Ce slug est déjà utilisé.');
        }

        if ($id === null) {
            $model->insert($data);
            $id = $model->getInsertID();
        } else {
            $model->update($id, $data);
        }

        return redirect()->to('/admin/missions/' . $id . '/edit')->with('message', 'Mission sauvegardée.');
    }

    public function destroy(int $id)
    {
        $model = model(MissionModel::class);
        $mission = $model->find($id);
        if ($mission === null) {
            return redirect()->to('/admin/missions')->with('error', 'Mission introuvable.');
        }
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->back()->with('error', 'Confirmation requise.');
        }
        $model->delete($id);
        return redirect()->to('/admin/missions')->with('message', '"' . esc($mission['name']) . '" supprimée (et les progressions joueur en cascade).');
    }
}
