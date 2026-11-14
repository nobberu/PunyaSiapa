<?php

require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class HomeController
{
    public function index()
    {
        require __DIR__ . '/../views/index.php';
    }

    /**
     * Halaman hasil pencarian FTS.
     * GET /search?q=kata+kunci&jenis=kehilangan
     */
    public function search()
    {
        $r     = new Request();
        $q     = trim((string) $r->query('q', ''));
        $jenis = (string) $r->query('jenis', '');

        // Sanitasi filter jenis
        if (!in_array($jenis, ['kehilangan', 'ditemukan'], true)) {
            $jenis = '';
        }

        $hasil = ($q !== '')
            ? (new Postingan())->search($q, $jenis)
            : [];

        echo View::render('search/index', [
            'q'     => $q,
            'jenis' => $jenis,
            'hasil' => $hasil,
        ]);
    }
}
