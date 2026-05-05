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
