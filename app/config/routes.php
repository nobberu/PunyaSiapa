<?php

/**
 * Definisi rute aplikasi.
 *
 * File ini di-require dari Kernel; variabel $router sudah tersedia.
 * Handler bisa berupa closure atau string "Controller@method".
 */

// Beranda
$router->get('/', 'HomeController@index');

// Contoh closure dengan parameter dinamis
$router->get('/halo/{nama}', function ($nama) {
    echo 'Halo, ' . htmlspecialchars($nama) . '!';
});

// Contoh 405 Method Not Allowed
$router->post('/', function () {
    echo 'POST ke beranda';
});

// Auth
$router->get('/auth/login',     'AuthController@showLogin');
$router->post('/auth/login',    'AuthController@login');
$router->get('/auth/register',  'AuthController@showRegister');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout',   'AuthController@logout');

// CRUD (aktifkan setelah controller dibuat):
// $router->get('/barang',          'BarangController@index');
// $router->get('/barang/{id:\d+}', 'BarangController@detail');
// $router->post('/barang',         'BarangController@store');