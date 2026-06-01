<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class PlayerItemModel extends Model
{
    protected $table         = 'player_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['player_id', 'item_id', 'equipped', 'quantity'];

    /**
     * Inventaire complet d'un player avec les détails item joints (slot, name, bonus...).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findFullInventory(int $playerId): array
    {
        return $this->select('player_items.*, items.slug AS item_slug, items.name AS item_name, items.slot AS item_slot, items.description AS item_description, items.bonus_force, items.bonus_blindage, items.bonus_reflexes, items.bonus_hack, items.discontinued AS item_discontinued, items.consumable_type AS item_consumable_type, items.image_path, items.model_path')
            ->join('items', 'items.id = player_items.item_id', 'inner')
            ->where('player_items.player_id', $playerId)
            ->orderBy('items.slot')
            ->orderBy('player_items.equipped', 'DESC')
            ->orderBy('items.name')
            ->findAll();
    }

    /**
     * Donne les 6 starters au player s'il n'a aucun item dans son inventaire (idempotent).
     */
    public function ensureStarterKit(int $playerId): void
    {
        $existing = $this->where('player_id', $playerId)->countAllResults();
        if ($existing > 0) {
            return;
        }

        $items = model(ItemModel::class)->findStarters();
        if (empty($items)) {
            return;
        }

        $now  = Time::now()->toDateTimeString();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'player_id'  => $playerId,
                'item_id'    => $item['id'],
                'equipped'   => 1,
                'quantity'   => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->insertBatch($rows);
    }

    /**
     * Équipe un item du player (le déséquipe si déjà équipé d'un autre dans le même slot).
     *
     * @return array{ok: bool, message: string}
     */
    public function equip(int $playerId, int $playerItemId): array
    {
        $row = $this->select('player_items.*, items.slot AS item_slot, items.name AS item_name, items.discontinued AS item_discontinued')
            ->join('items', 'items.id = player_items.item_id', 'inner')
            ->where('player_items.id', $playerItemId)
            ->where('player_items.player_id', $playerId)
            ->first();

        if ($row === null) {
            return ['ok' => false, 'message' => 'Item introuvable dans ton inventaire.'];
        }
        if ((int) $row['item_discontinued'] === 1) {
            return ['ok' => false, 'message' => 'Cet item est hors-circuit, impossible de l\'équiper.'];
        }

        $db = $this->db;
        $db->transStart();
        // 1) Déséquiper tous les autres items du même slot pour ce player.
        $db->query(
            'UPDATE player_items pi JOIN items i ON i.id = pi.item_id
             SET pi.equipped = 0, pi.updated_at = NOW()
             WHERE pi.player_id = ? AND i.slot = ? AND pi.equipped = 1',
            [$playerId, $row['item_slot']],
        );
        // 2) Équiper celui-ci.
        $this->update($playerItemId, ['equipped' => 1]);
        $db->transComplete();

        if ($db->transStatus()) {
            // Mission tracking pose au niveau model (humains + bots).
            model(MissionModel::class)->trackEvent($playerId, 'equip_slot', (string) $row['item_slot']);
        }

        return [
            'ok'      => $db->transStatus(),
            'message' => $db->transStatus() ? esc($row['item_name']) . ' équipé.' : 'Erreur transaction.',
        ];
    }

    /**
     * Déséquipe l'item actuellement équipé pour ce player dans le slot donné.
     *
     * @return array{ok: bool, message: string}
     */
    public function unequipSlot(int $playerId, string $slot): array
    {
        $affected = $this->db->query(
            'UPDATE player_items pi JOIN items i ON i.id = pi.item_id
             SET pi.equipped = 0, pi.updated_at = NOW()
             WHERE pi.player_id = ? AND i.slot = ? AND pi.equipped = 1',
            [$playerId, $slot],
        );

        return [
            'ok'      => true,
            'message' => 'Slot ' . esc($slot) . ' déséquipé.',
        ];
    }

    /**
     * Ajoute une quantite d'un item dans l'inventaire d'un joueur. Si le joueur a deja
     * un player_item NON EQUIPE sur cet item, on incremente sa quantity. Sinon on cree.
     *
     * Methode shared : utilisee par BazaarListingModel (achat + retour de listing),
     * et idealement par les futures sources de drops (crime rewards, mission rewards,
     * combat loot, gifts via messagerie).
     *
     * On agrege uniquement sur les rows non equipees pour ne pas modifier un slot equipe.
     */
    public function addStackable(int $playerId, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) return;
        $existing = $this->where('player_id', $playerId)
            ->where('item_id', $itemId)
            ->where('equipped', 0)
            ->first();
        if ($existing !== null) {
            $this->update($existing['id'], [
                'quantity'   => new \CodeIgniter\Database\RawSql('quantity + ' . $quantity),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }
        $this->insert([
            'player_id' => $playerId,
            'item_id'   => $itemId,
            'equipped'  => 0,
            'quantity'  => $quantity,
        ]);
    }
}
