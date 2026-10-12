<?php

require_once __DIR__ . '/../core/Auth.php';

class KehilanganController
{
    public function index()
    {
        echo View::render('kehilangan/index');
    }

    public function show($id)
    {
        Auth::requireLogin();

        echo View::render('kehilangan/[id]', ['id' => $id]);
    }
}
