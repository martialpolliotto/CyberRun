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
});
