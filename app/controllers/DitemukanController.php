<?php

require_once __DIR__ . '/../core/Auth.php';

class DitemukanController
{
    public function index()
    {
        echo View::render('ditemukan/index');
    }

    public function show($id)
    {
        Auth::requireLogin();

        echo View::render('ditemukan/[id]', ['id' => $id]);
    }
}
