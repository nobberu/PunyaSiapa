<?php

namespace punyaSiapa\Http;

// Dimuat di scope global (kernel.php di-include dari public/index.php), supaya
// variabel $db_* dari config.php tetap terbaca sebagai global oleh Database.
require_once __DIR__ . '/../../app/config/config.php';

/**
 * Kernel HTTP — pusat siklus hidup aplikasi.
 *
 * Menangani bootstrap, memuat rute, menjalankan dispatcher, menangkap output,
 * lalu membungkusnya menjadi objek Response.
 */
class Kernel
{
    /**
     * Jalankan seluruh aplikasi untuk satu request.
     */
    public function handle(Request $request): Response
    {
        // ------------------------------------------------------------
        // 1. Bootstrap
        // ------------------------------------------------------------
        if (!defined('BASE_PATH')) {
            $script = $request->server('SCRIPT_NAME') ?? '';
            $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
            $base   = ($base === '.' || $base === '') ? '' : $base;
            define('BASE_PATH', $base);
        }

        require_once __DIR__ . '/../../app/core/Router.php';
        require_once __DIR__ . '/../../app/core/Database.php';
        require_once __DIR__ . '/../../app/core/Auth.php';

        // Registri kelas yang dirujuk rute (belum ada autoloader).
        require_once __DIR__ . '/../../app/controllers/HomeController.php';
        require_once __DIR__ . '/../../app/controllers/AuthController.php';

        \Auth::start();

        // ------------------------------------------------------------
        // 2. Router + definisi rute
        // ------------------------------------------------------------
        $router = new \Router();
        require_once __DIR__ . '/../../app/config/routes.php';

        // ------------------------------------------------------------
        // 3. Dispatch, tangkap output
        // ------------------------------------------------------------
        ob_start();
        $router->run($request);
        $content = ob_get_clean();

        // ------------------------------------------------------------
        // 4. Bungkus menjadi Response
        // ------------------------------------------------------------
        return new Response($content, http_response_code() ?: 200);
    }
}