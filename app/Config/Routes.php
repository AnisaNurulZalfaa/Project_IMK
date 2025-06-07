<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->post('/register', 'Auth::register');
$routes->post('/login', 'Auth::login');

// $routes->get('test/start/(:num)', 'Test::startTest/$1'); // Memulai tes dengan ID Tes
// $routes->post('test/answer', 'Test::submitAnswerAndGetNextQuestion'); // Submit jawaban dan ambil soal berikutnya
// $routes->get('test/result/(:num)/(:num)', 'Test::getResult/$1/$2'); // Melihat hasil spesifik user/test (bisa dipanggil otomatis)

$routes->group('api', ['filter' => 'jwt'], static function ($routes) {
    $routes->get('test/start/(:num)', 'Test::startTest/$1'); // Memulai tes dengan ID Tes
    $routes->post('test/answer', 'Test::submitAnswerAndGetNextQuestion'); // Submit jawaban dan ambil soal berikutnya
    $routes->get('test/result/(:num)/(:num)', 'Test::getResult/$1/$2'); // Melihat hasil spesifik user/test (bisa dipanggil otomatis)
    $routes->patch('test/result/(:num)', 'Test::updateTestDetails/$1');
});
