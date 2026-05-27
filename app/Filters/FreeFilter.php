<?php

namespace App\Filters;

use App\Models\PlayerModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

/**
 * Empeche l'acces aux pages d'action quand le joueur est en prison ou a la cyberclinique.
 *
 * Applique aux routes "actives" du jeu (Lab, Equipment, Shops, Crimes, Fixers, Inventory...).
 * Les pages d'etat (Profile, Jail) restent accessibles via le filter 'session' seul.
 *
 * Si in_jail_until > now    -> redirect /jail
 * Si in_hospital_until > now -> redirect /profile (en attendant une page /hospital dediee)
 */
class FreeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }

        $player = model(PlayerModel::class)->findByUserId((int) auth()->user()->id);
        if ($player === null) {
            return null;
        }

        $now = Time::now();

        if (! empty($player['in_jail_until']) && Time::parse($player['in_jail_until'])->isAfter($now)) {
            return redirect()->to('/jail')->with('error', 'Tu es en prison, cette page t\'est inaccessible.');
        }

        if (! empty($player['in_hospital_until']) && Time::parse($player['in_hospital_until'])->isAfter($now)) {
            return redirect()->to('/profile')->with('error', 'Tu es a la cyberclinique, repose-toi.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // rien
    }
}
