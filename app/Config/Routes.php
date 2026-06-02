<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

// Routes toujours accessibles (en prison ou a la cyberclinique).
$routes->group('', ['filter' => 'session'], static function ($routes) {
    $routes->get('profile',         'Profile::index');
    $routes->get('profile/edit',    'Profile::edit');
    $routes->post('profile/save',   'Profile::save');
    $routes->get('jail', 'Jail::index');
    $routes->post('jail/escape', 'Jail::escape');

    // Log d'activite perso (toujours accessible).
    $routes->get('log', 'Logs::index');

    // Social (consultable en prison/hopital aussi).
    $routes->get('players', 'Players::index');
    $routes->get('players/(:segment)', 'Players::index/$1');
    $routes->get('u/(:segment)', 'Players::show/$1');
    $routes->get('leaderboards', 'Leaderboards::index');
    $routes->get('leaderboards/(:segment)', 'Leaderboards::index/$1');
});

// Routes "actives" : bloquees si le joueur est incarcere (prison ou hopital).
// Le filter 'free' redirige vers /jail ou /profile selon l'etat.
$routes->group('', ['filter' => ['session', 'free']], static function ($routes) {
    $routes->get('city', 'City::index');

    $routes->get('lab', 'Lab::index');
    $routes->post('lab/train/(:segment)', 'Lab::train/$1');

    $routes->get('equipment', 'Equipment::index');
    $routes->post('equipment/equip/(:num)', 'Equipment::equip/$1');
    $routes->post('equipment/unequip/(:segment)', 'Equipment::unequip/$1');

    $routes->get('inventory', 'Inventory::index');
    $routes->post('inventory/consume/(:num)', 'Inventory::consume/$1');
    $routes->post('inventory/sell/(:num)',    'Inventory::sellToVendor/$1');

    $routes->get('jobs',                   'Jobs::index');
    $routes->get('jobs/(:segment)',        'Jobs::show/$1');
    $routes->post('jobs/(:segment)/apply', 'Jobs::apply/$1');
    $routes->post('jobs/quit',             'Jobs::quit');

    // Actions sur les autres joueurs : faire evader (bust, nerve) ou payer caution (bail, credits).
    $routes->post('bust/(:num)', 'Players::bust/$1');
    $routes->post('bail/(:num)', 'Players::bail/$1');

    // Social : relations (toggle ami/ennemi/cible), bounties, transferts d'argent.
    $routes->post('relations/(:segment)/(:num)', 'Relations::toggle/$1/$2');
    $routes->get('bounties',                     'Bounties::index');
    $routes->post('bounties/place',              'Bounties::place');
    $routes->post('bounties/(:num)/cancel',      'Bounties::cancel/$1');
    $routes->post('transfer',                    'Transfer::send');

    // Messagerie privee 1-to-1.
    $routes->get('messages',                       'Messages::index');
    $routes->get('messages/thread/(:num)',         'Messages::thread/$1');
    $routes->post('messages/send',                 'Messages::send');

    // Stubs : espionnage (a brancher dans les phases suivantes).
    $routes->post('spy/(:num)',  'Players::spy/$1');

    // Combat : start (depuis profil), view, turn, post-action (mug/hospitalize/leave).
    $routes->post('attack/(:num)',                       'Combat::start/$1');
    $routes->get('combat/(:num)',                        'Combat::view/$1');
    $routes->post('combat/(:num)/turn',                  'Combat::turn/$1');
    $routes->post('combat/(:num)/post/(:segment)',       'Combat::postAction/$1/$2');

    $routes->get('shops', 'Shops::index');
    $routes->get('shop/(:segment)', 'Shops::show/$1');
    $routes->post('shop/(:segment)/buy/(:num)', 'Shops::buy/$1/$2');

    $routes->get('fixers', 'Fixers::index');
    $routes->get('fixers/(:segment)', 'Fixers::show/$1');
    $routes->post('fixers/accept/(:num)', 'Fixers::accept/$1');
    $routes->post('fixers/claim/(:num)', 'Fixers::claim/$1');

    $routes->get('crimes', 'Crimes::index');
    $routes->post('crimes/attempt/(:num)', 'Crimes::attempt/$1');
    $routes->get('crimes/(:segment)', 'Crimes::show/$1');

    // Dailies (3 defis quotidiens rotatifs).
    $routes->get('dailies',                           'Dailies::index');
    $routes->post('dailies/(:num)/claim',             'Dailies::claim/$1');

    // Wiki gameplay (read-only, source = docs/GAMEPLAY.md).
    $routes->get('wiki',                              'Wiki::index');
    $routes->get('wiki/(:segment)',                   'Wiki::show/$1');

    // Chat live (polling HTMX).
    $routes->get('chat',                              'Chat::index');
    $routes->get('chat/init/(:segment)',              'Chat::init/$1');
    $routes->get('chat/poll/(:segment)/(:num)',       'Chat::poll/$1/$2');
    $routes->post('chat/send',                        'Chat::send');
    $routes->get('chat/(:segment)',                   'Chat::index/$1');

    // Bazaar joueur-a-joueur.
    $routes->get('bazaar/mine',                       'Bazaar::mine');
    $routes->post('bazaar/list',                      'Bazaar::listFromInventory');
    $routes->post('bazaar/listings/(:num)/unlist',    'Bazaar::unlist/$1');
    $routes->post('bazaar/listings/(:num)/buy',       'Bazaar::buy/$1');

    // Factions.
    $routes->get('factions',                                     'Factions::index');
    $routes->get('factions/create',                              'Factions::createForm');
    $routes->post('factions/create',                             'Factions::create');
    $routes->get('factions/mine',                                'Factions::mine');
    $routes->get('factions/wars',                                'Factions::wars');
    $routes->post('factions/mine/wars/declare',                  'Factions::declareWar');
    $routes->post('factions/mine/wars/(:num)/accept',            'Factions::acceptWar/$1');
    $routes->post('factions/mine/wars/(:num)/reject',            'Factions::rejectWar/$1');
    $routes->post('factions/mine/leave',                         'Factions::leave');
    $routes->post('factions/mine/donate',                        'Factions::donate');
    $routes->post('factions/applications/mine/cancel',           'Factions::cancelMyApplication');
    $routes->post('factions/applications/(:num)/accept',         'Factions::acceptApplication/$1');
    $routes->post('factions/applications/(:num)/reject',         'Factions::rejectApplication/$1');
    $routes->post('factions/members/(:num)/kick',                'Factions::kick/$1');
    $routes->get('factions/(:num)',                              'Factions::show/$1');
    $routes->post('factions/(:num)/apply',                       'Factions::apply/$1');
});

// Zone admin : double filter (session + appartenance au groupe "admin").
$routes->group('admin', ['filter' => ['session', 'group:admin'], 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');

    $routes->get('logs', 'Logs::index');

    $routes->get('items',                  'Items::index');
    $routes->get('items/new',              'Items::new');
    $routes->post('items/save',            'Items::save');
    $routes->get('items/(:num)/edit',      'Items::edit/$1');
    $routes->post('items/(:num)/save',     'Items::save/$1');
    $routes->post('items/(:num)/discontinue', 'Items::discontinue/$1');
    $routes->post('items/(:num)/restore',     'Items::restore/$1');
    $routes->post('items/(:num)/destroy',     'Items::destroy/$1');

    $routes->get('vendors',                'Vendors::index');
    $routes->get('vendors/(:num)/edit',    'Vendors::edit/$1');
    $routes->post('vendors/(:num)/save',   'Vendors::save/$1');

    $routes->get('fixers',                  'Fixers::index');
    $routes->get('fixers/new',              'Fixers::new');
    $routes->post('fixers/save',            'Fixers::save');
    $routes->get('fixers/(:num)/edit',      'Fixers::edit/$1');
    $routes->post('fixers/(:num)/save',     'Fixers::save/$1');
    $routes->post('fixers/(:num)/destroy',  'Fixers::destroy/$1');

    $routes->get('missions',                  'Missions::index');
    $routes->get('missions/new',              'Missions::new');
    $routes->post('missions/save',            'Missions::save');
    $routes->get('missions/(:num)/edit',      'Missions::edit/$1');
    $routes->post('missions/(:num)/save',     'Missions::save/$1');
    $routes->post('missions/(:num)/destroy',  'Missions::destroy/$1');

    $routes->get('crime-categories',                  'CrimeCategories::index');
    $routes->get('crime-categories/new',              'CrimeCategories::new');
    $routes->post('crime-categories/save',            'CrimeCategories::save');
    $routes->get('crime-categories/(:num)/edit',      'CrimeCategories::edit/$1');
    $routes->post('crime-categories/(:num)/save',     'CrimeCategories::save/$1');
    $routes->post('crime-categories/(:num)/destroy',  'CrimeCategories::destroy/$1');

    $routes->get('crimes',                  'Crimes::index');
    $routes->get('crimes/new',              'Crimes::new');
    $routes->post('crimes/save',            'Crimes::save');
    $routes->get('crimes/(:num)/edit',      'Crimes::edit/$1');
    $routes->post('crimes/(:num)/save',     'Crimes::save/$1');
    $routes->post('crimes/(:num)/destroy',  'Crimes::destroy/$1');
    $routes->post('crimes/(:num)/texts/add',            'Crimes::addText/$1');
    $routes->post('crimes/(:num)/texts/(:num)/save',    'Crimes::updateText/$1/$2');
    $routes->post('crimes/(:num)/texts/(:num)/destroy', 'Crimes::deleteText/$1/$2');

    $routes->get('game-settings',       'GameSettings::index');
    $routes->post('game-settings/save', 'GameSettings::save');

    $routes->get('bots',                   'Bots::index');
    $routes->post('bots/populate',         'Bots::populate');
    $routes->post('bots/(:num)/destroy',   'Bots::destroy/$1');
    $routes->post('bots/destroy-all',      'Bots::destroyAll');

    $routes->get('player-tools',         'PlayerTools::index');
    $routes->post('player-tools/adjust', 'PlayerTools::adjust');
});
