<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$routes->group('', ['filter' => 'session'], static function ($routes) {
    $routes->get('profile', 'Profile::index');

    $routes->get('lab', 'Lab::index');
    $routes->post('lab/train/(:segment)', 'Lab::train/$1');

    $routes->get('equipment', 'Equipment::index');
    $routes->post('equipment/equip/(:num)', 'Equipment::equip/$1');
    $routes->post('equipment/unequip/(:segment)', 'Equipment::unequip/$1');

    $routes->get('shops', 'Shops::index');
    $routes->get('shop/(:segment)', 'Shops::show/$1');
    $routes->post('shop/(:segment)/buy/(:num)', 'Shops::buy/$1/$2');

    $routes->get('fixers', 'Fixers::index');
    $routes->get('fixers/(:segment)', 'Fixers::show/$1');
    $routes->post('fixers/accept/(:num)', 'Fixers::accept/$1');
    $routes->post('fixers/claim/(:num)', 'Fixers::claim/$1');
});

// Zone admin : double filter (session + appartenance au groupe "admin").
$routes->group('admin', ['filter' => ['session', 'group:admin'], 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');

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
});
