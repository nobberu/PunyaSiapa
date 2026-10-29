<?php

namespace punyaSiapa\Http;

require_once __DIR__ . '/../../app/config/config.php';

class Kernel
{
    // centered request
    public function handle(Request $request): Response
    {
        // bootstrapping
        if (!defined('BASE_PATH')) {
            $script = $request->server('SCRIPT_NAME') ?? '';
            $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
            $base   = ($base === '.' || $base === '') ? '' : $base;
            define('BASE_PATH', $base);
        }

        require_once __DIR__ . '/../../app/core/Router.php';
        require_once __DIR__ . '/../../app/core/Database.php';
        require_once __DIR__ . '/../../app/core/Auth.php';
        require_once __DIR__ . '/../../app/core/View.php';

        require_once __DIR__ . '/../../app/controllers/HomeController.php';
        require_once __DIR__ . '/../../app/controllers/AuthController.php';
        require_once __DIR__ . '/../../app/controllers/KehilanganController.php';
        require_once __DIR__ . '/../../app/controllers/DitemukanController.php';
        require_once __DIR__ . '/../../app/controllers/KomentarController.php';
                require_once __DIR__ . '/../../app/controllers/ClaimController.php';

        \Auth::start();

        // router
        $router = new \Router();
        require_once __DIR__ . '/../../app/config/routes.php';

        // catch output
        ob_start();
        $router->run($request);
        $content = ob_get_clean();

        // wrap to response
        return new Response($content, http_response_code() ?: 200);
    }
}