<?php

require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class HomeController
{
    public function index()
    {
        require __DIR__ . '/../views/index.php';
    }

    /**
     * Halaman pencarian FTS & Filter (jenis, kategori, status barang).
     * GET /search?q=...&jenis=...&kategori=...&status=...
     */
    public function search()
    {
        $r            = new Request();
        $q            = trim((string) $r->query('q', ''));
        $jenis        = (string) $r->query('jenis', '');
        $idKategori   = (int) $r->query('kategori', 0);
        $statusBarang = (string) $r->query('status', '');

        if (!in_array($jenis, ['kehilangan', 'ditemukan'], true)) {
            $jenis = '';
        }

        if (!in_array($statusBarang, ['aktif', 'selesai'], true)) {
            $statusBarang = '';
        }

        if ($idKategori < 0) {
            $idKategori = 0;
        }

        $adaFilter = ($q !== '' || $jenis !== '' || $idKategori > 0 || $statusBarang !== '');

        $hasil = $adaFilter
            ? (new Postingan())->search($q, $jenis, $idKategori, $statusBarang)
            : [];

        echo View::render('search/index', [
            'q'              => $q,
            'jenis'          => $jenis,
            'kategori'       => $idKategori,
            'status'         => $statusBarang,
            'daftarKategori' => (new Kategori())->all(),
            'adaFilter'      => $adaFilter,
            'hasil'          => $hasil,
        ]);
    }
}