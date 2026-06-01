<?php

namespace App\Controllers;

use App\Models\BazaarListingModel;
use App\Models\GameSettingModel;
use App\Models\PlayerItemModel;
use App\Models\PlayerModel;

/**
 * Bazaar joueur-a-joueur.
 *  - /bazaar/mine                          : mon bazaar (mes listings + form de listing)
 *  - /bazaar/list (POST)                   : creer un listing depuis un player_item
 *  - /bazaar/listings/{id}/unlist (POST)   : retirer un listing (items rendus)
 *  - /bazaar/listings/{id}/buy (POST)      : acheter un listing
 *
 * Les listings d'un autre joueur sont visibles sur sa fiche /u/{username}.
 */
class Bazaar extends BaseController
{
    public function mine()
    {
        $me = $this->requireMe();
        $listings   = model(BazaarListingModel::class)->listForSeller((int) $me['id']);
        $inventory  = model(PlayerItemModel::class)->findFullInventory((int) $me['id']);

        // On ne propose que les items non equipes et non hors-circuit pour le form.
        $listable = array_values(array_filter($inventory, static function (array $row): bool {
            return (int) $row['equipped'] === 0
                && (int) $row['item_discontinued'] === 0
                && (int) $row['quantity'] > 0;
        }));

        $settings = model(GameSettingModel::class);

        return view('bazaar/mine', [
            'me'           => $me,
            'listings'     => $listings,
            'listable'     => $listable,
            'fee_pct'      => (int) $settings->get('bazaar_fee_pct', 5),
            'max_listings' => (int) $settings->get('bazaar_max_listings_per_player', 50),
        ]);
    }

    public function listFromInventory()
    {
        $me = $this->requireMe();
        $r  = model(BazaarListingModel::class)->listFromInventory(
            (int) $me['id'],
            (int) $this->request->getPost('player_item_id'),
            (int) $this->request->getPost('quantity'),
            (int) $this->request->getPost('unit_price'),
        );

        $back = $this->safeReturnTo((string) $this->request->getPost('return_to'), '/bazaar/mine');
        return redirect()->to($back)->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function unlist(int $listingId)
    {
        $me = $this->requireMe();
        $r  = model(BazaarListingModel::class)->unlist($listingId, (int) $me['id']);
        return redirect()->to('/bazaar/mine')->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    public function buy(int $listingId)
    {
        $me = $this->requireMe();
        $qty = max(1, (int) $this->request->getPost('quantity'));
        $r   = model(BazaarListingModel::class)->buy($listingId, (int) $me['id'], $qty);

        $back = $this->safeReturnTo((string) $this->request->getPost('return_to'), '/players');
        return redirect()->to($back)->with($r['ok'] ? 'message' : 'error', $r['message']);
    }

    /**
     * Restreint return_to a un chemin interne ('/...' uniquement, pas '//' ni 'http://').
     * Evite l'open-redirect : un attaquant pourrait sinon forger un formulaire qui renvoie
     * l'utilisateur sur un site externe pour du phishing.
     */
    private function safeReturnTo(string $candidate, string $fallback): string
    {
        if ($candidate === '' || strlen($candidate) > 200) {
            return $fallback;
        }
        // Doit commencer par '/' sans '//' (protocol-relative) ni '/\' (Windows-style bypass).
        if ($candidate[0] !== '/' || str_starts_with($candidate, '//') || str_starts_with($candidate, '/\\')) {
            return $fallback;
        }
        return $candidate;
    }

    private function requireMe(): array
    {
        $me = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($me === null) {
            throw new \RuntimeException('Fiche player introuvable.');
        }
        return $me;
    }
}
