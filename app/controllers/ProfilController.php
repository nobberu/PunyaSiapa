<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Pengguna.php';
require_once __DIR__ . '/../models/Postingan.php';

class ProfilController
{
    public function index()
    {
        Auth::requireLogin();

        $idPengguna = (int) Auth::id();
        $pengguna   = (new Pengguna())->findById($idPengguna);

        if ($pengguna === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $postingan  = (new Postingan())->allByUser($idPengguna);
        $kehilangan = [];
        $ditemukan  = [];

        foreach ($postingan as $p) {
            if ($p['jenis_postingan'] === 'kehilangan') {
                $kehilangan[] = $p;
            } else {
                $ditemukan[] = $p;
            }
        }

        echo View::render('profil/index', [
            'pengguna'   => $pengguna,
            'statistik'  => (new Pengguna())->statistik($idPengguna),
            'kehilangan' => $kehilangan,
            'ditemukan'  => $ditemukan,
        ]);
    }
}
