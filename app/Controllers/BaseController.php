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
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
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

    /**
     * Resout le username (via users.username) d'un player_id donne. Fallback 'inconnu'.
     * Centralise pour eviter 5 copies du meme join dans les controllers/services.
     */
    protected function resolveUsername(int $playerId): string
    {
        $row = db_connect()->table('players p')
            ->select('users.username')
            ->join('users', 'users.id = p.user_id', 'inner')
            ->where('p.id', $playerId)
            ->get()->getRowArray();
        return (string) ($row['username'] ?? 'inconnu');
    }
}
