<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ==========================================================
// LANDING PAGE (Publik)
// ==========================================================
$routes->get('/', 'Home::index');

// ==========================================================
// AUTH ROUTES (Publik — tanpa filter auth, TAPI dilindungi rate limit + honeypot)
// ==========================================================
$routes->get('/login', 'AuthController::loginForm');
$routes->post('/login', 'AuthController::login', ['filter' => 'ratelimit:login,5,300']); // 5x per 5 menit
$routes->get('/logout', 'AuthController::logout');

$routes->get('/register', 'AuthController::registerForm');
$routes->post('/register', 'AuthController::register', ['filter' => ['honeypot', 'ratelimit:register,3,600']]); // 3x per 10 menit

$routes->get('/forgot-password', 'AuthController::forgotPasswordForm');
$routes->post('/forgot-password', 'AuthController::forgotPassword', ['filter' => 'ratelimit:forgot_password,3,600']); // 3x per 10 menit

$routes->get('/reset-password', 'AuthController::resetPasswordForm');
$routes->post('/reset-password', 'AuthController::resetPassword');

// ==========================================================
// AUTH ROUTES (Butuh login, semua role boleh akses)
// ==========================================================
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('/change-password', 'AuthController::changePasswordForm');
    $routes->post('/change-password', 'AuthController::changePassword');

    $routes->get('/profile', 'AuthController::profile');
    $routes->post('/profile', 'AuthController::updateProfile');

    $routes->get('/dashboard', 'DashboardController::index');
});

// ==========================================================
// MASTER DATA — hanya staff manajemen (super_admin, admin)
// ==========================================================
$routes->group('master', ['filter' => 'auth:super_admin,admin'], function ($routes) {
    // Facilities
    $routes->get('facilities', 'FacilityController::index');
    $routes->get('facilities/list', 'FacilityController::list');
    $routes->post('facilities/create', 'FacilityController::create');
    $routes->post('facilities/update/(:num)', 'FacilityController::update/$1');
    $routes->post('facilities/delete/(:num)', 'FacilityController::delete/$1');

    // Room Types
    $routes->get('room-types', 'RoomTypeController::index');
    $routes->get('room-types/list', 'RoomTypeController::list');
    $routes->get('room-types/facilities/(:num)', 'RoomTypeController::getFacilities/$1');
    $routes->post('room-types/create', 'RoomTypeController::create');
    $routes->post('room-types/update/(:num)', 'RoomTypeController::update/$1');
    $routes->post('room-types/delete/(:num)', 'RoomTypeController::delete/$1');

    // Rooms
    $routes->get('rooms', 'RoomController::index');
    $routes->get('rooms/list', 'RoomController::list');
    $routes->post('rooms/create', 'RoomController::create');
    $routes->post('rooms/update/(:num)', 'RoomController::update/$1');
    $routes->post('rooms/delete/(:num)', 'RoomController::delete/$1');

    // Customers
    $routes->get('customers', 'CustomerController::index');
    $routes->get('customers/list', 'CustomerController::list');
    $routes->post('customers/create', 'CustomerController::create');
    $routes->post('customers/update/(:num)', 'CustomerController::update/$1');
    $routes->post('customers/delete/(:num)', 'CustomerController::delete/$1');
});

// ==========================================================
// OPERASIONAL — staff (super_admin, admin, receptionist, manager)
// ==========================================================
$routes->group('reservation', ['filter' => 'auth:super_admin,admin,receptionist,manager'], function ($routes) {
    $routes->get('/', 'ReservationController::index');
    $routes->get('list', 'ReservationController::list');
    $routes->get('available-rooms', 'ReservationController::availableRooms');
    $routes->post('create', 'ReservationController::create');
    $routes->post('update-status/(:num)', 'ReservationController::updateStatus/$1');
});

$routes->group('checkin', ['filter' => 'auth:super_admin,admin,receptionist,manager'], function ($routes) {
    $routes->get('/', 'CheckInController::index');
    $routes->get('ready-list', 'CheckInController::readyList');
    $routes->get('today-list', 'CheckInController::todayList');
    $routes->post('process/(:num)', 'CheckInController::process/$1');
});

$routes->group('checkout', ['filter' => 'auth:super_admin,admin,receptionist,manager'], function ($routes) {
    $routes->get('/', 'CheckOutController::index');
    $routes->get('ready-list', 'CheckOutController::readyList');
    $routes->get('today-list', 'CheckOutController::todayList');
    $routes->get('preview/(:num)', 'CheckOutController::preview/$1');
    $routes->post('process/(:num)', 'CheckOutController::process/$1');
});

$routes->group('payment', ['filter' => 'auth:super_admin,admin,receptionist,manager'], function ($routes) {
    $routes->get('/', 'PaymentController::index');
    $routes->get('unpaid-list', 'PaymentController::unpaidList');
    $routes->get('detail/(:num)', 'PaymentController::detail/$1');
    $routes->post('create', 'PaymentController::create');
});

$routes->group('invoice', ['filter' => 'auth:super_admin,admin,receptionist,manager'], function ($routes) {
    $routes->get('/', 'InvoiceController::index');
    $routes->get('list', 'InvoiceController::list');
    $routes->get('view/(:num)', 'InvoiceController::view/$1');
    $routes->get('download/(:num)', 'InvoiceController::downloadPdf/$1');
    $routes->get('items/(:num)', 'InvoiceController::items/$1');
    $routes->post('items/add', 'InvoiceController::addItem');
    $routes->post('items/delete/(:num)', 'InvoiceController::deleteItem/$1');
});

// ==========================================================
// SELF-BOOKING — khusus customer, dilindungi honeypot + rate limit
// ==========================================================
$routes->group('my-reservations', ['filter' => 'auth:customer'], function ($routes) {
    $routes->get('/', 'ReservationController::selfBookingForm');
    $routes->post('store', 'ReservationController::selfBooking', ['filter' => ['honeypot', 'ratelimit:booking,10,3600']]); // 10x per jam
});

// ==========================================================
// REPORTS — hanya manager & admin ke atas
// ==========================================================
$routes->group('reports', ['filter' => 'auth:super_admin,admin,manager'], function ($routes) {
    $routes->get('/', 'ReportController::index');
    $routes->get('data/(:segment)', 'ReportController::data/$1');
    $routes->get('export-excel/(:segment)', 'ReportController::exportExcel/$1');
    $routes->get('export-pdf/(:segment)', 'ReportController::exportPdf/$1');
});

// ==========================================================
// SETTINGS — khusus super_admin
// ==========================================================
$routes->group('settings', ['filter' => 'auth:super_admin'], function ($routes) {
    $routes->get('/', 'SettingController::index');
    $routes->post('update', 'SettingController::update');
});
// ==========================================================
// USER MANAGEMENT & ACTIVITY LOG — khusus super_admin
// ==========================================================
$routes->group('users', ['filter' => 'auth:super_admin'], function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('list', 'UserController::list');
    $routes->post('create', 'UserController::create');
    $routes->post('update/(:num)', 'UserController::update/$1');
    $routes->post('toggle-active/(:num)', 'UserController::toggleActive/$1');
    $routes->post('reset-password/(:num)', 'UserController::resetPassword/$1');
});

$routes->group('activity-logs', ['filter' => 'auth:super_admin'], function ($routes) {
    $routes->get('/', 'ActivityLogController::index');
    $routes->get('list', 'ActivityLogController::list');
});