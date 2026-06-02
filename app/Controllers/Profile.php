<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\PlayerModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class Profile extends BaseController
{
    private const AVATARS_DIR = FCPATH . 'uploads/avatars/';
    private const AVATARS_URL = '/uploads/avatars/';
    private const ALLOWED_AVATAR_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_BIO_LEN = 2000;
    private const MAX_SIG_LEN = 200;

    public function index()
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId($user->id);

        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'profile');

        return view('profile', [
            'user'     => $user,
            'player'   => $player,
            'xpToNext' => $player['level'] * 100,
            'stats'    => $playerModel->getEffectiveStats((int) $player['id']),
        ]);
    }

    /** Page d'edition de la personnalisation : bio + signature + avatar. */
    public function edit()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        return view('profile_edit', [
            'user'         => $user,
            'player'       => $player,
            'max_bio_len'  => self::MAX_BIO_LEN,
            'max_sig_len'  => self::MAX_SIG_LEN,
            'allowed_exts' => self::ALLOWED_AVATAR_EXTS,
        ]);
    }

    public function save()
    {
        $user        = auth()->user();
        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $bio = trim((string) $this->request->getPost('bio'));
        $sig = trim((string) $this->request->getPost('signature'));

        if (mb_strlen($bio) > self::MAX_BIO_LEN) {
            return redirect()->to('/profile/edit')->withInput()->with('error', 'Bio trop longue (max ' . self::MAX_BIO_LEN . ' caractères).');
        }
        if (mb_strlen($sig) > self::MAX_SIG_LEN) {
            return redirect()->to('/profile/edit')->withInput()->with('error', 'Signature trop longue (max ' . self::MAX_SIG_LEN . ' caractères).');
        }

        $update = [
            'bio'        => $bio !== '' ? $bio : null,
            'signature'  => $sig !== '' ? $sig : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Avatar : upload optionnel.
        $avatarFile = $this->request->getFile('avatar');
        $avatarPath = $this->handleAvatarUpload($avatarFile, (int) $user->id);
        if ($avatarPath !== null) {
            $update['avatar_path'] = $avatarPath;
        }

        // Reset avatar si checkbox cochee.
        if ($this->request->getPost('avatar_reset') === '1') {
            $this->deleteAvatarFile($player['avatar_path'] ?? null);
            $update['avatar_path'] = null;
        }

        $playerModel->update((int) $player['id'], $update);
        return redirect()->to('/profile')->with('message', 'Profil mis a jour.');
    }

    /**
     * Upload avatar, valide extension, ecrit en uploads/avatars/{userId}.{ext},
     * retourne URL publique ou null si pas de fichier valide.
     */
    private function handleAvatarUpload(?UploadedFile $file, int $userId): ?string
    {
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $ext = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_AVATAR_EXTS, true)) {
            return null;
        }
        if (! is_dir(self::AVATARS_DIR)) {
            @mkdir(self::AVATARS_DIR, 0755, true);
        }
        $safeName = 'avatar-' . $userId . '.' . $ext;
        $file->move(self::AVATARS_DIR, $safeName, true);
        return self::AVATARS_URL . $safeName;
    }

    /** Supprime physiquement le fichier avatar precedent si present. */
    private function deleteAvatarFile(?string $path): void
    {
        if ($path === null || $path === '') return;
        $abs = self::AVATARS_DIR . basename($path);
        if (is_file($abs)) @unlink($abs);
    }
}
