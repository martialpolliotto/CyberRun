<?php

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\MissionModel;
use App\Models\PlayerItemModel;
use App\Models\PlayerModel;
use App\Models\VendorModel;

class Shops extends BaseController
{
    /** Vue d'ensemble : grille des 3 marchands. */
    public function index()
    {
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player !== null) {
            model(MissionModel::class)->trackEvent((int) $player['id'], 'visit_page', 'shops');
        }

        return view('shops/index', [
            'vendors' => model(VendorModel::class)->listAll(),
        ]);
    }

    /** Page d'un marchand : portrait + description + catalogue + boutons acheter. */
    public function show(string $slug)
    {
        $vendorModel = model(VendorModel::class);
        $vendor      = $vendorModel->findBySlug($slug);
        if ($vendor === null) {
            return redirect()->to('/shops')->with('error', 'Marchand introuvable.');
        }

        $catalog = $vendorModel->getCatalog((int) $vendor['id']);

        // Solde du joueur connecté pour griser les boutons des items inaccessibles.
        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);

        return view('shops/show', [
            'vendor'  => $vendor,
            'catalog' => $catalog,
            'player'  => $player,
        ]);
    }

    /** Action d'achat. Vérifie crédits, débite, ajoute à player_items (non équipé). */
    public function buy(string $slug, int $itemId)
    {
        $vendorModel = model(VendorModel::class);
        $vendor      = $vendorModel->findBySlug($slug);
        if ($vendor === null) {
            return redirect()->to('/shops')->with('error', 'Marchand introuvable.');
        }

        $itemModel = model(ItemModel::class);
        $item      = $itemModel->find($itemId);
        if ($item === null
            || (int) $item['vendor_id'] !== (int) $vendor['id']
            || (int) $item['discontinued'] === 1
            || (int) $item['price'] <= 0
        ) {
            return redirect()->to('/shop/' . $slug)->with('error', 'Item indisponible chez ce marchand.');
        }

        $playerModel = model(PlayerModel::class);
        $player      = $playerModel->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return redirect()->to('/')->with('error', 'Fiche player introuvable.');
        }

        $price = (int) $item['price'];
        if ((int) $player['credits'] < $price) {
            return redirect()->to('/shop/' . $slug)->with('error', 'Crédits insuffisants (' . $price . ' requis).');
        }

        // Transaction : débit + insertion player_item.
        $db = db_connect();
        $db->transStart();

        // Débit atomique : refuse si entre temps les credits sont passés en dessous.
        $playerModel->builder()
            ->where('id', $player['id'])
            ->where('credits >=', $price)
            ->update([
                'credits'    => new \CodeIgniter\Database\RawSql('credits - ' . $price),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        $affected = $db->affectedRows();

        if ($affected === 0) {
            $db->transRollback();
            return redirect()->to('/shop/' . $slug)->with('error', 'Achat échoué (crédits insuffisants ou conflit).');
        }

        model(PlayerItemModel::class)->insert([
            'player_id' => (int) $player['id'],
            'item_id'   => (int) $item['id'],
            'equipped'  => 0,
            'quantity'  => 1,
        ]);

        $db->transComplete();

        // Hooks missions : un achat reussi compte pour buy_item (target = slug vendor ou '*').
        model(MissionModel::class)->trackEvent((int) $player['id'], 'buy_item', (string) $vendor['slug']);

        return redirect()->to('/shop/' . $slug)
            ->with('message', '"' . esc($item['name']) . '" acheté pour ' . number_format($price) . ' crédits.');
    }
}
