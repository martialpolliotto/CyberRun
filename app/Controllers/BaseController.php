<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Helpers globaux : player (resolve_username), time (relative_short).
        // Disponibles sans besoin d'appeler helper() dans chaque controller/view.
        $this->helpers = ['player', 'time'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Daily login streak : credite le reward au 1er hit du jour. No-op les autres requetes.
        $this->maybeTrackDailyLogin();
    }

    /**
     * Si user logge + fiche player + pas un bot, appelle PlayerModel::recordDailyLogin
     * une fois par requete. La methode est elle-meme idempotente par jour (verifie
     * last_login_at avant de crediter). Set un flash success au 1er crediting.
     */
    private function maybeTrackDailyLogin(): void
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) return;
        $me = $this->me();
        if ($me === null || (int) ($me['is_bot'] ?? 0) === 1) return;
        // Skip pour les routes HTMX/poll pour ne pas spammer le flash dans des reponses partielles.
        if ($this->isHtmx()) return;

        $r = model(\App\Models\PlayerModel::class)->recordDailyLogin((int) $me['id']);
        if ($r['credited']) {
            $msg = $r['broken']
                ? 'Bon retour. Streak réinitialisé à 1. Bonus +' . number_format($r['reward']) . '¢.'
                : 'Connexion jour ' . $r['streak'] . ' — bonus +' . number_format($r['reward']) . '¢.';
            session()->setFlashdata('message', $msg);
        }
    }

    /** True si la requete vient de htmx (header HX-Request: true). */
    protected function isHtmx(): bool
    {
        return $this->request->getHeaderLine('HX-Request') === 'true';
    }

    /**
     * Helper : ajoute un header HX-Redirect a une response pour forcer un full navigate
     * cote client. Utile quand l'action a un effet hors de la zone affichee (ex: critique
     * combat envoie a /jail).
     */
    protected function htmxRedirect(string $url): ResponseInterface
    {
        return $this->response->setHeader('HX-Redirect', $url)->setStatusCode(204);
    }

    /**
     * Memo de la fiche player pour le user connecte. Permet d'eviter N findByUserId
     * dans la meme requete (ex: sidebar + widget + controller faisaient 3 lookups).
     * @var array<string,mixed>|null|false  false = not loaded yet
     */
    private $cachedMe = false;

    /**
     * Retourne la fiche player du user authentifie, ou null si pas connecte / pas de fiche.
     * Memoize pour la duree de la requete.
     *
     * @return array<string,mixed>|null
     */
    protected function me(): ?array
    {
        if ($this->cachedMe !== false) {
            return $this->cachedMe;
        }
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return $this->cachedMe = null;
        }
        return $this->cachedMe = model(\App\Models\PlayerModel::class)
            ->findByUserId((int) auth()->user()->id);
    }

    /**
     * Comme me() mais throw si la fiche n'existe pas. Pour les controllers
     * qui n'ont pas de sens sans player. L'exception remonte en 500, ce qui est
     * le bon signal : avoir un user logge sans fiche player est un bug d'init.
     *
     * @return array<string,mixed>
     */
    protected function requireMe(): array
    {
        $me = $this->me();
        if ($me === null) {
            throw new \RuntimeException('Fiche player introuvable.');
        }
        return $me;
    }

    /** @deprecated utiliser resolve_username() global a la place. */
    protected function resolveUsername(int $playerId): string
    {
        return resolve_username($playerId);
    }
}
