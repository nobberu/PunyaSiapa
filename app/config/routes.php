<?php

/** @var Router $router */

// Beranda
$router->get("/", "HomeController@index");

// Contoh 405 Method Not Allowed
$router->post("/", function () {
    echo "POST ke beranda";
});

// Auth
$router->get("/login", "AuthController@showLogin");
$router->post("/login", "AuthController@login");
$router->get("/register", "AuthController@showRegister");
$router->post("/register", "AuthController@register");
$router->post("/logout", "AuthController@logout");

// Kehilangan (CRUD)
$router->get("/kehilangan", "KehilanganController@index");
$router->get("/kehilangan/create", "KehilanganController@create");
$router->post("/kehilangan", "KehilanganController@store");
$router->get("/kehilangan/{id:\d+}", "KehilanganController@show");
$router->get("/kehilangan/{id:\d+}/edit", "KehilanganController@edit");
$router->post("/kehilangan/{id:\d+}", "KehilanganController@update");
$router->post("/kehilangan/{id:\d+}/delete", "KehilanganController@destroy");

// Ditemukan (CRUD)
$router->get("/ditemukan", "DitemukanController@index");
$router->get("/ditemukan/create", "DitemukanController@create");
$router->post("/ditemukan", "DitemukanController@store");
$router->get("/ditemukan/{id:\d+}", "DitemukanController@show");
$router->get("/ditemukan/{id:\d+}/edit", "DitemukanController@edit");
$router->post("/ditemukan/{id:\d+}", "DitemukanController@update");
$router->post("/ditemukan/{id:\d+}/delete", "DitemukanController@destroy");

// Komentar
$router->post("/kehilangan/{id:\d+}/komentar", "KomentarController@store");
$router->post("/ditemukan/{id:\d+}/komentar", "KomentarController@store");
$router->post("/komentar/{id:\d+}/delete", "KomentarController@destroy");

// Klaim
$router->post("/ditemukan/{id:\d+}/claim", "ClaimController@store");
