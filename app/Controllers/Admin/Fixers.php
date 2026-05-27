<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FixerModel;
use App\Models\MissionModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class Fixers extends BaseController
{
    private const MAX_IMAGE_SIZE     = 2 * 1024 * 1024;
    private const ALLOWED_IMAGE_EXTS = ['png', 'jpg', 'jpeg', 'webp'];
    private const UPLOADS_DIR        = FCPATH . 'uploads/fixers/';
    private const PUBLIC_URL         = '/uploads/fixers/';

    public function index()
    {
        $fixers = model(FixerModel::class)->listAll();

        $missionCounts = [];
        foreach ($fixers as $f) {
            $missionCounts[$f['id']] = model(MissionModel::class)->where('fixer_id', $f['id'])->countAllResults();
        }

        return view('admin/fixers/index', [
            'fixers'        => $fixers,
            'missionCounts' => $missionCounts,
        ]);
    }

    public function new()
    {
        return view('admin/fixers/form', ['fixer' => null]);
    }

    public function edit(int $id)
    {
        $fixer = model(FixerModel::class)->find($id);
        if ($fixer === null) {
            return redirect()->to('/admin/fixers')->with('error', 'Fixer introuvable.');
        }
        return view('admin/fixers/form', ['fixer' => $fixer]);
    }

    public function save(?int $id = null)
    {
        $model = model(FixerModel::class);
        $existing = $id ? $model->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to('/admin/fixers')->with('error', 'Fixer introuvable.');
        }

        $data = [
            'slug'         => trim($this->request->getPost('slug') ?? ''),
            'name'         => trim($this->request->getPost('name') ?? ''),
            'tagline'      => trim($this->request->getPost('tagline') ?? '') ?: null,
            'description'  => trim($this->request->getPost('description') ?? '') ?: null,
            'unlock_order' => max(1, (int) $this->request->getPost('unlock_order')),
        ];

        if ($data['slug'] === '' || $data['name'] === '') {
            return redirect()->back()->withInput()->with('error', 'Slug et nom sont obligatoires.');
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

        $imagePath = $this->handleUpload($this->request->getFile('image'), $data['slug']);
        if ($imagePath !== null) {
            $model->update($id, ['image_path' => $imagePath]);
        }

        return redirect()->to('/admin/fixers/' . $id . '/edit')->with('message', 'Fixer sauvegardé.');
    }

    public function destroy(int $id)
    {
        $model = model(FixerModel::class);
        $fixer = $model->find($id);
        if ($fixer === null) {
            return redirect()->to('/admin/fixers')->with('error', 'Fixer introuvable.');
        }
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->back()->with('error', 'Confirmation requise.');
        }
        if (! empty($fixer['image_path'])) {
            $abs = self::UPLOADS_DIR . basename($fixer['image_path']);
            if (is_file($abs)) { @unlink($abs); }
        }
        $model->delete($id);
        return redirect()->to('/admin/fixers')->with('message', '"' . esc($fixer['name']) . '" supprimé (et ses missions en cascade).');
    }

    private function handleUpload(?UploadedFile $file, string $slug): ?string
    {
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            return null;
        }
        $ext = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_IMAGE_EXTS, true)) {
            return null;
        }
        if (! is_dir(self::UPLOADS_DIR)) {
            @mkdir(self::UPLOADS_DIR, 0755, true);
        }
        $safeName = preg_replace('/[^a-z0-9_-]/i', '', $slug) . '-img.' . $ext;
        $file->move(self::UPLOADS_DIR, $safeName, true);
        return self::PUBLIC_URL . $safeName;
    }
}
