<?php

/**
 * Front Controller PunyaSIapa.
 *
 * Titik masuk tunggal: semua request (via public/.htaccess) masuk ke sini,
 * lalu seluruh siklus hidup aplikasi ditangani oleh Kernel.
 */

declare(strict_types=1);

$projectRoot = is_dir(__DIR__ . '/src/http') ? __DIR__ : dirname(__DIR__);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($path !== '/' && is_file($projectRoot . '/public' . $path)) {
        return false;
    }
}

require_once $projectRoot . '/src/http/request.php';
require_once $projectRoot . '/src/http/response.php';
require_once $projectRoot . '/src/http/kernel.php';

use punyaSiapa\Http\Kernel;
use punyaSiapa\Http\Request;

$response = (new Kernel())->handle(new Request());
$response->send();