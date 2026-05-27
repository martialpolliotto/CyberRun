<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ItemModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class Items extends BaseController
{
    /** Taille max upload, en octets. */
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;       // 2 MB
    private const MAX_MODEL_SIZE = 10 * 1024 * 1024;      // 10 MB

    private const ALLOWED_IMAGE_EXTS = ['png', 'jpg', 'jpeg', 'webp'];
    private const ALLOWED_MODEL_EXTS = ['glb', 'gltf'];

    private const UPLOADS_DIR = FCPATH . 'uploads/items/';   // FCPATH = public/
    private const PUBLIC_URL  = '/uploads/items/';

    public function index()
    {
        $items = model(ItemModel::class)->orderBy('discontinued')->orderBy('slot')->orderBy('name')->findAll();

        // owners count par item (pour la colonne "joueurs")
        $owners = [];
        foreach ($items as $it) {
            $owners[$it['id']] = model(ItemModel::class)->countOwners((int) $it['id']);
        }

        return view('admin/items/index', [
            'items'  => $items,
            'owners' => $owners,
            'slots'  => ItemModel::SLOTS,
        ]);
    }

    public function new()
    {
        return view('admin/items/form', [
            'item'  => null,
            'slots' => ItemModel::SLOTS,
        ]);
    }

    public function edit(int $id)
    {
        $item = model(ItemModel::class)->find($id);
        if ($item === null) {
            return redirect()->to('/admin/items')->with('error', 'Item introuvable.');
        }

        return view('admin/items/form', [
            'item'   => $item,
            'slots'  => ItemModel::SLOTS,
            'owners' => model(ItemModel::class)->countOwners($id),
        ]);
    }

    public function save(?int $id = null)
    {
        $itemModel = model(ItemModel::class);
        $existing  = $id ? $itemModel->find($id) : null;

        if ($id !== null && $existing === null) {
            return redirect()->to('/admin/items')->with('error', 'Item introuvable.');
        }

        $consumableType = $this->request->getPost('consumable_type');
        $consumableType = in_array($consumableType, ItemModel::CONSUMABLE_TYPES, true) ? $consumableType : null;

        $data = [
            'slug'           => trim($this->request->getPost('slug') ?? ''),
            'name'           => trim($this->request->getPost('name') ?? ''),
            'description'    => trim($this->request->getPost('description') ?? ''),
            'slot'           => $this->request->getPost('slot') ?? '',
            'bonus_force'    => (int) $this->request->getPost('bonus_force'),
            'bonus_blindage' => (int) $this->request->getPost('bonus_blindage'),
            'bonus_reflexes' => (int) $this->request->getPost('bonus_reflexes'),
            'bonus_hack'     => (int) $this->request->getPost('bonus_hack'),
            'starter'        => $this->request->getPost('starter') ? 1 : 0,
            'vendor_id'      => $this->request->getPost('vendor_id') !== '' ? (int) $this->request->getPost('vendor_id') : null,
            'price'          => max(0, (int) $this->request->getPost('price')),
            // Consommables
            'consumable_type'              => $consumableType,
            'cooldown_seconds'             => max(0, (int) $this->request->getPost('cooldown_seconds')),
            'effect_hp'                    => (int) $this->request->getPost('effect_hp'),
            'effect_nrg'                   => (int) $this->request->getPost('effect_nrg'),
            'effect_nrv'                   => (int) $this->request->getPost('effect_nrv'),
            'effect_force'                 => (int) $this->request->getPost('effect_force'),
            'effect_blindage'              => (int) $this->request->getPost('effect_blindage'),
            'effect_reflexes'              => (int) $this->request->getPost('effect_reflexes'),
            'effect_hack'                  => (int) $this->request->getPost('effect_hack'),
            'effect_hp_max'                => (int) $this->request->getPost('effect_hp_max'),
            'effect_nrg_max'               => (int) $this->request->getPost('effect_nrg_max'),
            'effect_nrv_max'               => (int) $this->request->getPost('effect_nrv_max'),
            'effect_duration_seconds'      => max(0, (int) $this->request->getPost('effect_duration_seconds')),
            'addiction_threshold_increase' => max(0, (int) $this->request->getPost('addiction_threshold_increase')),
            'overdose_chance_pct'          => max(0, min(99, (int) $this->request->getPost('overdose_chance_pct'))),
            'overdose_hospital_min'        => max(0, (int) $this->request->getPost('overdose_hospital_min')),
            'overdose_hospital_max'        => max(0, (int) $this->request->getPost('overdose_hospital_max')),
        ];

        // Validation manuelle simple (on évitera CI4 Validation pour MVP).
        if ($data['slug'] === '' || $data['name'] === '' || ! isset(ItemModel::SLOTS[$data['slot']])) {
            return redirect()->back()->withInput()->with('error', 'Slug, nom et slot sont obligatoires.');
        }
        // Slug unique (sauf pour soi-même en édition)
        $slugCheck = $itemModel->where('slug', $data['slug']);
        if ($id !== null) {
            $slugCheck->where('id !=', $id);
        }
        if ($slugCheck->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Ce slug est déjà utilisé.');
        }

        // Save (insert or update)
        if ($id === null) {
            $itemModel->insert($data);
            $id = $itemModel->getInsertID();
        } else {
            $itemModel->update($id, $data);
        }

        // Uploads (après le save pour avoir l'ID + slug à jour)
        $imagePath = $this->handleUpload(
            $this->request->getFile('image'),
            $data['slug'],
            'img',
            self::ALLOWED_IMAGE_EXTS,
            self::MAX_IMAGE_SIZE,
        );
        $modelPath = $this->handleUpload(
            $this->request->getFile('model'),
            $data['slug'],
            'model',
            self::ALLOWED_MODEL_EXTS,
            self::MAX_MODEL_SIZE,
        );

        $mediaUpdate = [];
        if ($imagePath !== null) {
            $mediaUpdate['image_path'] = $imagePath;
        }
        if ($modelPath !== null) {
            $mediaUpdate['model_path'] = $modelPath;
        }
        if (! empty($mediaUpdate)) {
            $itemModel->update($id, $mediaUpdate);
        }

        return redirect()->to('/admin/items/' . $id . '/edit')->with('message', 'Item sauvegardé.');
    }

    public function discontinue(int $id)
    {
        $itemModel = model(ItemModel::class);
        $item      = $itemModel->find($id);
        if ($item === null) {
            return redirect()->to('/admin/items')->with('error', 'Item introuvable.');
        }
        $itemModel->discontinue($id);
        return redirect()->to('/admin/items')->with('message', '"' . esc($item['name']) . '" mis hors-circuit. Tous les joueurs équipés ont été déséquipés.');
    }

    public function restore(int $id)
    {
        $itemModel = model(ItemModel::class);
        $item      = $itemModel->find($id);
        if ($item === null) {
            return redirect()->to('/admin/items')->with('error', 'Item introuvable.');
        }
        $itemModel->restore($id);
        return redirect()->to('/admin/items')->with('message', '"' . esc($item['name']) . '" réintroduit au catalogue.');
    }

    public function destroy(int $id)
    {
        $itemModel = model(ItemModel::class);
        $item      = $itemModel->find($id);
        if ($item === null) {
            return redirect()->to('/admin/items')->with('error', 'Item introuvable.');
        }
        // Sécurité : la case "Je confirme" doit être cochée
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->back()->with('error', 'Tu dois cocher la confirmation pour supprimer définitivement.');
        }

        // Supprime les fichiers media associés (si présents)
        foreach (['image_path', 'model_path'] as $col) {
            if (! empty($item[$col])) {
                $absPath = self::UPLOADS_DIR . basename($item[$col]);
                if (is_file($absPath)) {
                    @unlink($absPath);
                }
            }
        }

        // Hard delete : cascade sur player_items via FK
        $itemModel->delete($id);
        return redirect()->to('/admin/items')->with('message', '"' . esc($item['name']) . '" supprimé définitivement.');
    }

    /**
     * Gère un upload : valide ext + taille, déplace dans uploads/items/, retourne le path public ou null.
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
