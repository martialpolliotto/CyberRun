<?php

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\Model;

/**
 * Bazaar joueur-a-joueur.
 *
 * Modele de listing : 1 ligne = N exemplaires d'un meme item a un meme prix unitaire,
 * mis en vente par un joueur. Les items en listing sont sortis de l'inventaire du
 * vendeur (player_items.quantity decrementee).
 *
 * Toutes les operations critiques (create/unlist/buy) sont transactionnelles.
 */
class BazaarListingModel extends Model
{
    protected $table         = 'bazaar_listings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'seller_player_id', 'item_id', 'quantity', 'unit_price',
    ];

    /** Listings d'un vendeur, avec details item. */
    public function listForSeller(int $sellerId): array
    {
        return $this->select('bazaar_listings.*, items.name AS item_name, items.slug AS item_slug, items.slot AS item_slot, items.consumable_type AS item_consumable_type, items.discontinued AS item_discontinued')
            ->join('items', 'items.id = bazaar_listings.item_id', 'inner')
            ->where('bazaar_listings.seller_player_id', $sellerId)
            ->orderBy('bazaar_listings.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Cree (ou agrege a) un listing depuis un player_item donne.
     *
     * @return array{ok: bool, message: string, listing_id?: int}
     */
    public function listFromInventory(int $playerId, int $playerItemId, int $quantity, int $unitPrice): array
    {
        if ($quantity <= 0) {
            return ['ok' => false, 'message' => 'Quantite invalide.'];
        }
        if ($unitPrice <= 0) {
            return ['ok' => false, 'message' => 'Prix invalide.'];
        }

        $piModel = model(PlayerItemModel::class);
        $pi = $piModel->where('id', $playerItemId)->where('player_id', $playerId)->first();
        if ($pi === null) {
            return ['ok' => false, 'message' => 'Item introuvable dans ton inventaire.'];
        }
        if ((int) $pi['equipped'] === 1) {
            return ['ok' => false, 'message' => 'Desequipe d\'abord cet item.'];
        }
        if ((int) $pi['quantity'] < $quantity) {
            return ['ok' => false, 'message' => 'Tu n\'as pas autant d\'exemplaires.'];
        }

        // Verifie limite max listings.
        $max = (int) model(GameSettingModel::class)->get('bazaar_max_listings_per_player', 50);
        $current = $this->where('seller_player_id', $playerId)->countAllResults();
        if ($current >= $max) {
            return ['ok' => false, 'message' => 'Tu as atteint la limite de ' . $max . ' listings actifs.'];
        }

        $db = db_connect();
        $db->transStart();

        // Decremente atomiquement le player_item (guard >= quantity).
        // Sans ce guard, 2 listings paralleles sur le meme player_item dupliquent les items.
        $piModel->builder()
            ->where('id', $playerItemId)
            ->where('player_id', $playerId)
            ->where('equipped', 0)
            ->where('quantity >=', $quantity)
            ->update([
                'quantity'   => new RawSql('quantity - ' . $quantity),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Item indisponible (quantite changee entre-temps).'];
        }
        // Cleanup ligne a zero.
        $piModel->builder()
            ->where('id', $playerItemId)
            ->where('quantity', 0)
            ->delete();

        // Aggregation : si meme item + meme prix existe deja, incremente la quantity. Sinon insere.
        $existing = $this->where('seller_player_id', $playerId)
            ->where('item_id', (int) $pi['item_id'])
            ->where('unit_price', $unitPrice)
            ->first();

        if ($existing !== null) {
            $this->update($existing['id'], [
                'quantity'   => new RawSql('quantity + ' . $quantity),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $listingId = (int) $existing['id'];
        } else {
            $this->insert([
                'seller_player_id' => $playerId,
                'item_id'          => (int) $pi['item_id'],
                'quantity'         => $quantity,
                'unit_price'       => $unitPrice,
            ]);
            $listingId = (int) $this->getInsertID();
        }

        $db->transComplete();

        return ['ok' => true, 'message' => 'Listing cree.', 'listing_id' => $listingId];
    }

    /**
     * Annule (totalement) un listing, retourne les items dans l'inventaire.
     *
     * @return array{ok: bool, message: string}
     */
    public function unlist(int $listingId, int $sellerId): array
    {
        $listing = $this->find($listingId);
        if ($listing === null || (int) $listing['seller_player_id'] !== $sellerId) {
            return ['ok' => false, 'message' => 'Listing introuvable.'];
        }

        $db = db_connect();
        $db->transStart();

        model(PlayerItemModel::class)->addStackable($sellerId, (int) $listing['item_id'], (int) $listing['quantity']);
        $this->delete($listingId);

        $db->transComplete();

        return ['ok' => true, 'message' => 'Listing annule, items rendus.'];
    }

    /**
     * Achete N exemplaires d'un listing. Fee sink applique sur le total.
     *
     * @return array{ok: bool, message: string, total?: int, fee?: int, net?: int}
     */
    public function buy(int $listingId, int $buyerId, int $quantity): array
    {
        if ($quantity <= 0) {
            return ['ok' => false, 'message' => 'Quantite invalide.'];
        }

        $listing = $this->find($listingId);
        if ($listing === null) {
            return ['ok' => false, 'message' => 'Listing introuvable ou deja vendu.'];
        }
        $sellerId = (int) $listing['seller_player_id'];
        if ($sellerId === $buyerId) {
            return ['ok' => false, 'message' => 'Tu ne peux pas acheter ton propre listing.'];
        }
        if ((int) $listing['quantity'] < $quantity) {
            return ['ok' => false, 'message' => 'Stock insuffisant sur ce listing.'];
        }

        $total = (int) $listing['unit_price'] * $quantity;
        $feePct = (int) model(GameSettingModel::class)->get('bazaar_fee_pct', 5);
        $fee    = (int) floor($total * $feePct / 100);
        $net    = $total - $fee;

        $playerModel = model(PlayerModel::class);
        $buyer       = $playerModel->find($buyerId);
        if ($buyer === null || (int) $buyer['credits'] < $total) {
            return ['ok' => false, 'message' => 'Credits insuffisants.'];
        }

        $db = db_connect();
        $db->transStart();

        // Decremente atomiquement la quantite du listing (guard >= quantity).
        // C'est ce check-and-decrement qui empeche 2 acheteurs concurrents de vider le meme stock.
        $this->builder()
            ->where('id', $listingId)
            ->where('quantity >=', $quantity)
            ->update([
                'quantity'   => new RawSql('quantity - ' . $quantity),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        if ($db->affectedRows() === 0) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Stock insuffisant sur ce listing.'];
        }

        if (! $playerModel->debitAtomic($buyerId, $total)) {
            $db->transRollback();
            return ['ok' => false, 'message' => 'Credits insuffisants au moment du debit.'];
        }
        // Credit vendeur (net = total - fee, fee sink).
        $playerModel->creditUnconditional($sellerId, $net);

        // Cleanup : si le listing est vide, on le supprime.
        $this->builder()
            ->where('id', $listingId)
            ->where('quantity', 0)
            ->delete();

        // Ajoute items a l'acheteur.
        model(PlayerItemModel::class)->addStackable($buyerId, (int) $listing['item_id'], $quantity);

        $db->transComplete();

        return [
            'ok'      => true,
            'message' => 'Achat reussi : ' . $quantity . ' x ' . number_format((int) $listing['unit_price']) . '¢',
            'total'   => $total,
            'fee'     => $fee,
            'net'     => $net,
        ];
    }

}
