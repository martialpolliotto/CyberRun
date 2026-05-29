<?php

namespace App\Controllers;

use App\Models\FactionApplicationModel;
use App\Models\FactionMemberModel;
use App\Models\FactionModel;
use App\Models\GameSettingModel;
use App\Models\PlayerModel;
use CodeIgniter\Database\RawSql;

/**
 * Factions MVP : crews persistants.
 *  - /factions                : liste publique
 *  - /factions/create         : form fondation
 *  - /factions/{id}           : page publique d'une faction
 *  - /factions/{id}/apply     : POST candidature
 *  - /factions/mine           : dashboard interne (visible des membres)
 *  - /factions/mine/leave     : POST quitter (interdit au leader sauf si seul)
 *  - /factions/mine/donate    : POST donation tresorerie
 *  - /factions/applications/{appId}/accept|reject : POST leader
 *  - /factions/members/{playerId}/kick : POST leader
 *  - /factions/applications/mine/cancel : POST candidat annule sa propre
 */
class Factions extends BaseController
{
    public function index()
    {
        $me = $this->resolveMe();
        return view('factions/index', [
            'me'                => $me,
            'factions'          => model(FactionModel::class)->listAll(100),
            'my_pending'        => $me !== null ? model(FactionApplicationModel::class)->pendingForPlayer((int) $me['id']) : null,
            'create_cost'       => (int) model(GameSettingModel::class)->get('faction_create_cost', 100000),
            'create_min_level'  => (int) model(GameSettingModel::class)->get('faction_create_min_level', 5),
        ]);
    }

    public function show(int $factionId)
    {
        $me      = $this->resolveMe();
        $faction = model(FactionModel::class)->findWithLeader($factionId);
        if ($faction === null) {
            return redirect()->to('/factions')->with('error', 'Faction introuvable.');
        }
        return view('factions/show', [
            'me'         => $me,
            'faction'    => $faction,
            'members'    => model(FactionMemberModel::class)->listForFaction($factionId),
            'my_pending' => $me !== null ? model(FactionApplicationModel::class)->pendingForPlayer((int) $me['id']) : null,
            'is_member'  => $me !== null && (int) ($me['faction_id'] ?? 0) === $factionId,
        ]);
    }

    public function createForm()
    {
        $me = $this->requireMe();
        if (! empty($me['faction_id'])) {
            return redirect()->to('/factions/mine')->with('error', 'Tu fais deja partie d\'une faction.');
        }
        $settings = model(GameSettingModel::class);
        return view('factions/create', [
            'me'         => $me,
            'cost'       => (int) $settings->get('faction_create_cost', 100000),
            'min_level'  => (int) $settings->get('faction_create_min_level', 5),
            'name_max'   => FactionModel::NAME_MAX_LEN,
            'tag_max'    => FactionModel::TAG_MAX_LEN,
        ]);
    }

    public function create()
    {
        $me = $this->requireMe();
        $r  = model(FactionModel::class)->create(
            (int) $me['id'],
            (string) $this->request->getPost('name'),
            (string) $this->request->getPost('tag'),
            trim((string) $this->request->getPost('description')) ?: null,
        );
        if (! $r['ok']) {
            return redirect()->back()->withInput()->with('error', $r['message']);
        }
        return redirect()->to('/factions/mine')->with('message', $r['message']);
    }

    public function apply(int $factionId)
    {
        $me = $this->requireMe();
        $r  = model(FactionApplicationModel::class)->apply(
            (int) $me['id'],
            $factionId,
            trim((string) $this->request->getPost('message')) ?: null,
        );
        return redirect()->to('/factions/' . $factionId)
            ->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function cancelMyApplication()
    {
        $me  = $this->requireMe();
        $app = model(FactionApplicationModel::class)->pendingForPlayer((int) $me['id']);
        if ($app === null) {
            return redirect()->to('/factions')->with('error', 'Pas de candidature en attente.');
        }
        $r = model(FactionApplicationModel::class)->cancel((int) $app['id'], (int) $me['id']);
        return redirect()->to('/factions')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function mine()
    {
        $me = $this->requireMe();
        if (empty($me['faction_id'])) {
            return redirect()->to('/factions')->with('error', 'Tu n\'es dans aucune faction.');
        }
        $factionId = (int) $me['faction_id'];
        $faction   = model(FactionModel::class)->findWithLeader($factionId);
        if ($faction === null) {
            return redirect()->to('/factions')->with('error', 'Faction introuvable.');
        }
        $isLeader = (int) $faction['leader_player_id'] === (int) $me['id'];
        return view('factions/mine', [
            'me'           => $me,
            'faction'      => $faction,
            'members'      => model(FactionMemberModel::class)->listForFaction($factionId),
            'applications' => $isLeader ? model(FactionApplicationModel::class)->listPendingForFaction($factionId) : [],
            'is_leader'    => $isLeader,
        ]);
    }

    public function leave()
    {
        $me = $this->requireMe();
        if (empty($me['faction_id'])) {
            return redirect()->to('/factions')->with('error', 'Tu n\'es dans aucune faction.');
        }
        $factionId = (int) $me['faction_id'];
        $faction   = model(FactionModel::class)->find($factionId);
        if ($faction === null) {
            return redirect()->to('/factions');
        }
        $isLeader = (int) $faction['leader_player_id'] === (int) $me['id'];
        if ($isLeader && (int) $faction['members_count'] > 1) {
            return redirect()->to('/factions/mine')->with('error', 'Le leader ne peut pas quitter tant qu\'il reste d\'autres membres. Transfere d\'abord (a venir) ou kick tout le monde.');
        }
        if ($isLeader && (int) $faction['members_count'] <= 1) {
            // Solo : on dissout la faction.
            $db = db_connect();
            $db->transStart();
            model(FactionMemberModel::class)->where('faction_id', $factionId)->delete();
            model(PlayerModel::class)->update((int) $me['id'], ['faction_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            model(FactionModel::class)->delete($factionId);
            $db->transComplete();
            return redirect()->to('/factions')->with('message', 'Faction dissoute.');
        }
        model(FactionMemberModel::class)->removeMember($factionId, (int) $me['id']);
        return redirect()->to('/factions')->with('message', 'Tu as quitte la faction.');
    }

    public function donate()
    {
        $me = $this->requireMe();
        if (empty($me['faction_id'])) {
            return redirect()->to('/factions')->with('error', 'Tu n\'es dans aucune faction.');
        }
        $amount = max(0, (int) $this->request->getPost('amount'));
        if ($amount <= 0) {
            return redirect()->to('/factions/mine')->with('error', 'Montant invalide.');
        }

        $playerModel = model(PlayerModel::class);
        $db          = db_connect();
        $db->transStart();

        $playerModel->builder()
            ->where('id', (int) $me['id'])
            ->where('credits >=', $amount)
            ->update([
                'credits'    => new RawSql('credits - ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return redirect()->to('/factions/mine')->with('error', 'Credits insuffisants.');
        }

        $db->table('factions')
            ->where('id', (int) $me['faction_id'])
            ->update([
                'treasury'   => new RawSql('treasury + ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $db->table('faction_members')
            ->where('faction_id', (int) $me['faction_id'])
            ->where('player_id',  (int) $me['id'])
            ->update([
                'contributed_credits' => new RawSql('contributed_credits + ' . $amount),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

        $db->transComplete();
        return redirect()->to('/factions/mine')->with('message', 'Don de ' . number_format($amount) . ' credits a la tresorerie.');
    }

    public function acceptApplication(int $appId)
    {
        $me = $this->requireMe();
        $r  = model(FactionApplicationModel::class)->accept($appId, (int) $me['id']);
        return redirect()->to('/factions/mine')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function rejectApplication(int $appId)
    {
        $me = $this->requireMe();
        $r  = model(FactionApplicationModel::class)->reject($appId, (int) $me['id']);
        return redirect()->to('/factions/mine')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function kick(int $playerId)
    {
        $me = $this->requireMe();
        if (empty($me['faction_id'])) {
            return redirect()->to('/factions')->with('error', 'Tu n\'es dans aucune faction.');
        }
        $factionId = (int) $me['faction_id'];
        $faction   = model(FactionModel::class)->find($factionId);
        if ($faction === null || (int) $faction['leader_player_id'] !== (int) $me['id']) {
            return redirect()->to('/factions/mine')->with('error', 'Seul le leader peut kicker.');
        }
        if ($playerId === (int) $faction['leader_player_id']) {
            return redirect()->to('/factions/mine')->with('error', 'Le leader ne peut pas se kicker lui-meme.');
        }
        $member = model(FactionMemberModel::class)->findByPlayer($playerId);
        if ($member === null || (int) $member['faction_id'] !== $factionId) {
            return redirect()->to('/factions/mine')->with('error', 'Ce joueur n\'est pas membre.');
        }
        model(FactionMemberModel::class)->removeMember($factionId, $playerId);
        return redirect()->to('/factions/mine')->with('message', 'Membre exclu.');
    }

    /** @return array<string,mixed>|null */
    private function resolveMe(): ?array
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }
        return model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
    }

    /** Comme resolveMe mais redirige si pas de fiche. */
    private function requireMe(): array
    {
        $me = $this->resolveMe();
        if ($me === null) {
            throw new \RuntimeException('Fiche player introuvable.');
        }
        return $me;
    }
}
