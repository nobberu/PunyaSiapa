<?php

/**
 * Front Controller PunyaSIapa.
 *
 * Titik masuk tunggal: semua request (via public/.htaccess) masuk ke sini,
 * lalu seluruh siklus hidup aplikasi ditangani oleh Kernel.
 */

require_once __DIR__ . '/../src/http/request.php';
require_once __DIR__ . '/../src/http/response.php';
require_once __DIR__ . '/../src/http/kernel.php';

use punyaSiapa\Http\Kernel;
use punyaSiapa\Http\Request;

$response = (new Kernel())->handle(new Request());
$response->send();