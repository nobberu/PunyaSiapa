<?php

/**
 * Contoh controller. Karena belum ada autoloader, controller lain cukup
 * dibuat dengan pola yang sama dan di-require dari public/index.php.
 */
class HomeController
{
    public function index()
    {
        echo 'PunyaSIapa - router aktif.';
    }
}
