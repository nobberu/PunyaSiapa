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
