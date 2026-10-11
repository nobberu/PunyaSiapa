<?php

require_once __DIR__ . '/../core/Auth.php';

class DitemukanController
{
    public function index()
    {
        require __DIR__ . '/../views/ditemukan/index.php';
    }

    public function show($id)
    {
        Auth::requireLogin();

        require __DIR__ . '/../views/ditemukan/[id].php';
    }
}
