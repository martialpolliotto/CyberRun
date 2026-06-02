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
            'user'           => $user,
            'player'         => $player,
            'xpToNext'       => $player['level'] * 100,
            'stats'          => $playerModel->getEffectiveStats((int) $player['id']),
            'recent_attacks' => model(\App\Models\CombatModel::class)->recentAttacksOn((int) $player['id'], 10),
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

    /** RGPD : page tableau de bord des donnees personnelles (export + suppression). */
    public function data()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }
        return view('profile_data', ['user' => $user, 'player' => $player]);
    }

    /**
     * RGPD : export de toutes les donnees du compte au format JSON downloadable.
     * Inclut user (sans password_hash), player, et toutes les rows liees par player_id.
     */
    public function export()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $playerId = (int) $player['id'];
        $userId   = (int) $user->id;
        $db = db_connect();

        $data = [
            'export_date' => date('c'),
            'user' => [
                'id'         => $userId,
                'username'   => $user->username,
                'email'      => $user->email,
                'created_at' => (string) ($user->created_at ?? ''),
            ],
            'player'                 => $player,
            'player_items'           => $db->table('player_items')->where('player_id', $playerId)->get()->getResultArray(),
            'player_active_effects'  => $db->table('player_active_effects')->where('player_id', $playerId)->get()->getResultArray(),
            'player_crime_progress'  => $db->table('player_crime_progress')->where('player_id', $playerId)->get()->getResultArray(),
            'player_combat_stats'    => $db->table('player_combat_stats')->where('player_id', $playerId)->get()->getResultArray(),
            'player_missions'        => $db->table('player_missions')->where('player_id', $playerId)->get()->getResultArray(),
            'player_relations'       => $db->table('player_relations')->where('player_id', $playerId)->get()->getResultArray(),
            'player_mutes'           => $db->table('player_mutes')->where('player_id', $playerId)->get()->getResultArray(),
            'player_achievements'    => $db->table('player_achievements')->where('player_id', $playerId)->get()->getResultArray(),
            'daily_assignments'      => $db->table('daily_assignments')->where('player_id', $playerId)->get()->getResultArray(),
            'bank_deposits'          => $db->table('bank_deposits')->where('player_id', $playerId)->get()->getResultArray(),
            'bazaar_listings'        => $db->table('bazaar_listings')->where('seller_player_id', $playerId)->get()->getResultArray(),
            'messages_sent'          => $db->table('messages')->where('sender_player_id',    $playerId)->get()->getResultArray(),
            'messages_received'      => $db->table('messages')->where('recipient_player_id', $playerId)->get()->getResultArray(),
            'chat_messages_sent'     => $db->table('chat_messages')->where('sender_player_id', $playerId)->get()->getResultArray(),
            'combats_as_attacker'    => $db->table('combats')->where('attacker_player_id', $playerId)->get()->getResultArray(),
            'combats_as_defender'    => $db->table('combats')->where('defender_player_id', $playerId)->get()->getResultArray(),
            'bounties_placed'        => $db->table('bounties')->where('placer_player_id', $playerId)->get()->getResultArray(),
            'bounties_on_me'         => $db->table('bounties')->where('target_player_id', $playerId)->get()->getResultArray(),
            'spy_attempts_done'      => $db->table('spy_attempts')->where('spy_player_id',    $playerId)->get()->getResultArray(),
            'spy_attempts_on_me'     => $db->table('spy_attempts')->where('target_player_id', $playerId)->get()->getResultArray(),
            'faction_memberships'    => $db->table('faction_members')->where('player_id', $playerId)->get()->getResultArray(),
            'faction_applications'   => $db->table('faction_applications')->where('player_id', $playerId)->get()->getResultArray(),
            'activity_logs'          => $db->table('activity_logs')->where('player_id', $playerId)->get()->getResultArray(),
        ];

        $filename = 'cyberrun-export-' . $user->username . '-' . date('Ymd-His') . '.json';
        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * RGPD : suppression definitive du compte.
     * Confirmation par password + saisie du mot 'SUPPRIMER'.
     * Hard delete via Shield (cascade DB couvre toutes les tables liees).
     */
    public function delete()
    {
        $user   = auth()->user();
        $player = model(PlayerModel::class)->findByUserId($user->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $confirm  = trim((string) $this->request->getPost('confirm'));
        $password = (string) $this->request->getPost('password');

        if ($confirm !== 'SUPPRIMER') {
            return redirect()->to('/profile/data')->with('error', 'Saisis exactement le mot SUPPRIMER pour confirmer.');
        }

        // Verification mot de passe via Shield.
        $check = auth()->check([
            'email'    => $user->email,
            'password' => $password,
        ]);
        if (! $check->isOK()) {
            return redirect()->to('/profile/data')->with('error', 'Mot de passe incorrect.');
        }

        // Supprime l'avatar physique avant le delete user.
        if (! empty($player['avatar_path'])) {
            $this->deleteAvatarFile($player['avatar_path']);
        }

        // Hard delete user Shield -> CASCADE supprime player + toutes les rows
        // liees par FK (combats, messages, etc., toutes en ON DELETE CASCADE).
        $userId = (int) $user->id;
        $userProvider = service('users');
        $userProvider->delete($userId, true); // true = hard delete

        // Logout (clean session) + redirect home.
        auth()->logout();

        return redirect()->to('/')->with('message', 'Ton compte a ete supprime. Toutes tes donnees ont ete effacees.');
    }
}
