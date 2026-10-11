<?php

require_once __DIR__ . '/../core/Auth.php';

class KehilanganController
{
    public function index()
    {
        require __DIR__ . '/../views/kehilangan/index.php';
    }

    public function show($id)
    {
        Auth::requireLogin();

        require __DIR__ . '/../views/kehilangan/[id].php';
    }
}
