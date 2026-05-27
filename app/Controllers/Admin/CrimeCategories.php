<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CrimeCategoryModel;
use App\Models\CrimeModel;

class CrimeCategories extends BaseController
{
    public function index()
    {
        $cats = model(CrimeCategoryModel::class)->listAll();
        $counts = [];
        foreach ($cats as $c) {
            $counts[$c['id']] = model(CrimeModel::class)->where('category_id', $c['id'])->countAllResults();
        }
        return view('admin/crime_categories/index', ['categories' => $cats, 'counts' => $counts]);
    }

    public function new()
    {
        return view('admin/crime_categories/form', ['category' => null]);
    }

    public function edit(int $id)
    {
        $c = model(CrimeCategoryModel::class)->find($id);
        if ($c === null) {
            return redirect()->to('/admin/crime-categories')->with('error', 'Categorie introuvable.');
        }
        return view('admin/crime_categories/form', ['category' => $c]);
    }

    public function save(?int $id = null)
    {
        $model = model(CrimeCategoryModel::class);
        $existing = $id ? $model->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to('/admin/crime-categories')->with('error', 'Categorie introuvable.');
        }

        $stat = trim($this->request->getPost('primary_stat') ?? '');
        $data = [
            'slug'          => trim($this->request->getPost('slug') ?? ''),
            'name'          => trim($this->request->getPost('name') ?? ''),
            'description'   => trim($this->request->getPost('description') ?? '') ?: null,
            'primary_stat'  => in_array($stat, CrimeCategoryModel::VALID_STATS, true) ? $stat : null,
            'display_order' => max(1, (int) $this->request->getPost('display_order')),
        ];

        if ($data['slug'] === '' || $data['name'] === '') {
            return redirect()->back()->withInput()->with('error', 'Slug et nom sont obligatoires.');
        }
        $slugCheck = $model->where('slug', $data['slug']);
        if ($id !== null) { $slugCheck->where('id !=', $id); }
        if ($slugCheck->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Slug deja utilise.');
        }

        if ($id === null) {
            $model->insert($data);
            $id = $model->getInsertID();
        } else {
            $model->update($id, $data);
        }
        return redirect()->to('/admin/crime-categories/' . $id . '/edit')->with('message', 'Categorie sauvegardee.');
    }

    public function destroy(int $id)
    {
        $model = model(CrimeCategoryModel::class);
        $c = $model->find($id);
        if ($c === null) {
            return redirect()->to('/admin/crime-categories')->with('error', 'Categorie introuvable.');
        }
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->back()->with('error', 'Confirmation requise.');
        }
        $model->delete($id);
        return redirect()->to('/admin/crime-categories')->with('message', 'Categorie supprimee (crimes en cascade).');
    }
}
