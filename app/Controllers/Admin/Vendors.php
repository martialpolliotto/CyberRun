<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VendorModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class Vendors extends BaseController
{
    private const MAX_IMAGE_SIZE     = 2 * 1024 * 1024;
    private const ALLOWED_IMAGE_EXTS = ['png', 'jpg', 'jpeg', 'webp'];
    private const UPLOADS_DIR        = FCPATH . 'uploads/vendors/';
    private const PUBLIC_URL         = '/uploads/vendors/';

    public function index()
    {
        return view('admin/vendors/index', [
            'vendors' => model(VendorModel::class)->listAll(),
        ]);
    }

    public function edit(int $id)
    {
        $vendor = model(VendorModel::class)->find($id);
        if ($vendor === null) {
            return redirect()->to('/admin/vendors')->with('error', 'Marchand introuvable.');
        }
        return view('admin/vendors/form', ['vendor' => $vendor]);
    }

    public function save(int $id)
    {
        $vendorModel = model(VendorModel::class);
        $vendor      = $vendorModel->find($id);
        if ($vendor === null) {
            return redirect()->to('/admin/vendors')->with('error', 'Marchand introuvable.');
        }

        $data = [
            'name'        => trim($this->request->getPost('name') ?? ''),
            'tagline'     => trim($this->request->getPost('tagline') ?? ''),
            'description' => trim($this->request->getPost('description') ?? ''),
        ];
        if ($data['name'] === '') {
            return redirect()->back()->withInput()->with('error', 'Le nom est obligatoire.');
        }
        $vendorModel->update($id, $data);

        $imagePath = $this->handleUpload(
            $this->request->getFile('image'),
            $vendor['slug'],
            'portrait',
            self::ALLOWED_IMAGE_EXTS,
            self::MAX_IMAGE_SIZE,
        );
        if ($imagePath !== null) {
            $vendorModel->update($id, ['image_path' => $imagePath]);
        }

        $bannerPath = $this->handleUpload(
            $this->request->getFile('banner'),
            $vendor['slug'],
            'banner',
            self::ALLOWED_IMAGE_EXTS,
            self::MAX_IMAGE_SIZE,
        );
        if ($bannerPath !== null) {
            $vendorModel->update($id, ['banner_path' => $bannerPath]);
        }

        return redirect()->to('/admin/vendors/' . $id . '/edit')->with('message', 'Marchand sauvegardé.');
    }

    /**
     * Helper upload (dupliqué de Items.php — à factoriser en Service si 3e usage apparaît).
     */
    private function handleUpload(?UploadedFile $file, string $slug, string $kind, array $allowedExts, int $maxSize): ?string
    {
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        if ($file->getSize() > $maxSize) {
            return null;
        }
        $ext = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if (! in_array($ext, $allowedExts, true)) {
            return null;
        }
        if (! is_dir(self::UPLOADS_DIR)) {
            @mkdir(self::UPLOADS_DIR, 0755, true);
        }
        $safeName = preg_replace('/[^a-z0-9_-]/i', '', $slug) . '-' . $kind . '.' . $ext;
        $file->move(self::UPLOADS_DIR, $safeName, true);
        return self::PUBLIC_URL . $safeName;
    }
}
