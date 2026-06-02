<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate limit générique via le Throttler CI4 (token bucket sur cache).
 *
 * Usage en route : ['filter' => 'rate:action:N/S']
 *  - action : nom canonique de l'action (pour la clef cache)
 *  - N      : nombre max de hits dans la fenêtre
 *  - S      : durée de la fenêtre (secondes)
 *
 * Exemple :
 *   ['filter' => 'rate:attack:30/3600']  // max 30 attaques par heure
 *
 * Clef de comptage :
 *  - si user authentifié : action + 'u' + user_id (cap par compte)
 *  - sinon              : action + 'ip' + ip (cap par IP, ex: login/register)
 *
 * Quand depasse : redirect back avec flash error si web, sinon 429 JSON.
 */
class RateLimit implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments) || empty($arguments[0])) return;

        // Reassemble : CI4 split sur ':' donc on recompose tout sauf le premier 'rate:'.
        $spec = implode(':', $arguments);
        [$action, $rate] = $this->parseSpec($spec);
        if ($rate === null) return;
        [$capacity, $seconds] = $rate;

        $throttler = service('throttler');
        $key = $this->makeKey($action, $request);

        if ($throttler->check($key, $capacity, $seconds) === false) {
            $remaining = $throttler->getTokenTime();
            $msg = 'Trop d\'actions trop rapidement. Retente dans ' . $remaining . ' seconde' . ($remaining > 1 ? 's' : '') . '.';

            // API/JSON : 429.
            $accept = $request->getHeaderLine('Accept');
            if (str_contains($accept, 'application/json') || $request->getHeaderLine('HX-Request') === 'true') {
                return service('response')->setStatusCode(429)->setJSON(['ok' => false, 'error' => $msg]);
            }
            // Web : flash + redirect back.
            session()->setFlashdata('error', $msg);
            return redirect()->back();
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}

    /** Parse "action:N/S" -> ['action', [N, S]]. Renvoie [null, null] si format invalide. */
    private function parseSpec(string $spec): array
    {
        if (! str_contains($spec, ':') || ! str_contains($spec, '/')) {
            return [null, null];
        }
        [$action, $rate] = explode(':', $spec, 2);
        [$count, $secs]  = explode('/', $rate, 2);
        $count = (int) $count;
        $secs  = (int) $secs;
        if ($count <= 0 || $secs <= 0) return [null, null];
        return [$action, [$count, $secs]];
    }

    private function makeKey(string $action, RequestInterface $request): string
    {
        if (function_exists('auth') && auth()->loggedIn()) {
            return $action . ':u' . (int) auth()->user()->id;
        }
        return $action . ':ip' . $request->getIPAddress();
    }
}
