
<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('api/cities', 'CityController::index');

$routes->get('api/companies', 'CompanyController::index');
$routes->get('api/companies/(:num)', 'CompanyController::show/$1');

// Recherche et consultation des voyages (accès public).
$routes->get('api/trips', 'Api\TripController::index');
$routes->get('api/trips/(:num)', 'Api\TripController::show/$1');

$routes->get('api/trips/(:num)/seats', 'Api\TripController::seats/$1');


// Réservation : accès réservé aux utilisateurs authentifiés.
$routes->post(
    'api/reservations',
    'Api\ReservationController::test',
    ['filter' => 'tokens']
);
$routes->get(
    'api/reservations',
    'Api\ReservationController::history',
    ['filter' => 'tokens']
);

$routes->get(
    'api/reservations/(:num)',
    'Api\ReservationController::show/$1',
    ['filter' => 'tokens']
);



$routes->options('api/reservations/(:num)', static function () {});

$routes->patch(
    'api/reservations/(:num)/cancel',
    'Api\ReservationController::cancel/$1',
    ['filter' => 'tokens']
);

$routes->options(
    'api/reservations/(:num)/cancel',
    static function () {}
);

// Consultation des tickets du voyageur connecté.
$routes->get(
    'api/tickets',
    'Api\TicketController::index',
    ['filter' => 'tokens']
);

$routes->get(
    'api/tickets/(:num)',
    'Api\TicketController::show/$1',
    ['filter' => 'tokens']
);

// Téléchargement PDF du ticket du voyageur connecté.
$routes->get(
    'api/tickets/(:num)/pdf',
    'Api\TicketController::pdf/$1',
    ['filter' => 'tokens']
);

$routes->options(
    'api/tickets/(:num)/pdf',
    static function () {}
);

// Prévol CORS
$routes->options('api/tickets', static function () {});
$routes->options('api/tickets/(:num)', static function () {});



// Paiement simulé : accès réservé aux utilisateurs authentifiés.
$routes->post(
    'api/payments',
    'Api\PaymentController::testCreate',
    ['filter' => 'tokens']
);

$routes->post(
    'api/payments/simulate',
    'Api\PaymentController::testProcess',
    ['filter' => 'tokens']
);

// Authentification API
$routes->post('api/auth/register', 'Api\AuthController::register');
$routes->post('api/auth/login', 'Api\AuthController::login');

// Routes protégées par Bearer Token
$routes->get(
    'api/auth/me',
    'Api\AuthController::me',
    ['filter' => 'tokens']
);

$routes->post(
    'api/auth/logout',
    'Api\AuthController::logout',
    ['filter' => 'tokens']
);

$routes->post('api/chatbot/message', 'Api\ChatbotController::message', ['filter' => 'tokens']);

// Tableaux de bord : accès réservé aux utilisateurs authentifiés.
$routes->get(
    'api/admin/dashboard',
    'Api\DashboardController::admin',
    ['filter' => 'tokens']
);

$routes->get(
    'api/company/dashboard',
    'Api\DashboardController::company',
    ['filter' => 'tokens']
);

// Prévol CORS des tableaux de bord.
$routes->options('api/admin/dashboard', static function () {});
$routes->options('api/company/dashboard', static function () {});

// CORS : autoriser les requêtes preflight pour le login.
$routes->options('api/auth/register', static function () {
});
$routes->options('api/auth/login', static function () {
});

$routes->options('api/reservations', static function () {});

// Routes OPTIONS pour les requêtes preflight CORS.
$routes->options('api/cities', static function () {});

$routes->options('api/trips', static function () {});
$routes->options('api/trips/(:num)', static function () {});

$routes->options('api/trips/(:num)/seats', static function () {});

$routes->options('api/companies', static function () {});
$routes->options('api/companies/(:num)', static function () {});



$routes->options('api/auth/me', static function () {});
$routes->options('api/auth/logout', static function () {});



$routes->options('api/payments', static function () {});
$routes->options('api/payments/simulate', static function () {});

$routes->options('api/chatbot/message', static function () {});