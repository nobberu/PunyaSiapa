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
    
    public function update()
    {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/profil');
            exit;
        }

        $idPengguna  = (int) Auth::id();
        $nama        = trim($_POST['nama'] ?? '');
        $nomorKontak = trim($_POST['nomor_kontak'] ?? '');
        $fotoPath    = null;

        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $tmpName   = $_FILES['foto_profil']['tmp_name'];
            $extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $filename  = 'avatar_' . $idPengguna . '_' . time() . '.' . $extension;
            $targetDir = __DIR__ . '/../../public/uploads/avatars/';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (move_uploaded_file($tmpName, $targetDir . $filename)) {
                $fotoPath = BASE_PATH . '/uploads/avatars/' . $filename;
            }
        }

        $penggunaModel = new Pengguna();
        $penggunaModel->updateProfil($idPengguna, [
            'nama'         => $nama,
            'nomor_kontak' => $nomorKontak,
            'foto_profil'  => $fotoPath
        ]);

        header('Location: ' . BASE_PATH . '/profil');
        exit;
    }
}

