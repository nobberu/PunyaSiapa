<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Pengguna.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class ProfilController
{
    private const MAX_FILE_SIZE      = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const ALLOWED_MIMES      = ['image/jpeg', 'image/png'];

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

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            $this->redirectProfil('error', 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
        }

        $idPengguna  = (int) Auth::id();
        $nama        = trim((string) $r->post('nama', ''));
        $nomorKontak = trim((string) $r->post('nomor_kontak', ''));

        if ($nama === '') {
            $this->redirectProfil('error', 'Nama lengkap wajib diisi.');
        }

        if ($this->len($nama) > 100) {
            $this->redirectProfil('error', 'Nama lengkap maksimal 100 karakter.');
        }

        if (!preg_match('/^\+?[0-9]{8,20}$/', $nomorKontak)) {
            $this->redirectProfil('error', 'Nomor kontak harus 8–20 digit angka (boleh diawali +).');
        }

        [$fotoPath, $uploadError] = $this->handleAvatarUpload($r, $idPengguna);
        if ($uploadError !== null) {
            $this->redirectProfil('error', $uploadError);
        }

        (new Pengguna())->updateProfil($idPengguna, [
            'nama'         => $nama,
            'nomor_kontak' => $nomorKontak,
            'foto_profil'  => $fotoPath,
        ]);

        // Sinkronkan nama di session agar header langsung ter-update
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['nama'] = $nama;
        }

        $this->redirectProfil('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Validasi & simpan avatar baru (JPG/PNG, maks 2 MB, cek MIME riil).
     *
     * @return array{0:?string, 1:?string} [path_foto, pesan_error]
     */
    private function handleAvatarUpload(Request $r, int $idPengguna): array
    {
        $file = $r->file('foto_profil');

        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Terjadi kesalahan saat mengunggah foto profil.'];
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return [null, 'Ukuran foto profil maksimal 2 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [null, 'Format foto profil hanya boleh JPG atau PNG.'];
        }

        $mime = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        }

        if ($mime !== null && !in_array($mime, self::ALLOWED_MIMES, true)) {
            return [null, 'Berkas harus berupa gambar JPG atau PNG yang valid.'];
        }

        $targetDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $normalizedExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        $filename      = 'avatar_' . $idPengguna . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $normalizedExt;

        if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            return [null, 'Gagal menyimpan foto profil ke server.'];
        }

        return [BASE_PATH . '/uploads/avatars/' . $filename, null];
    }

    private function len(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function redirectProfil(string $type, string $message): void
    {
        $_SESSION['flash_profil'] = ['type' => $type, 'message' => $message];
        header('Location: ' . BASE_PATH . '/profil');
        exit;
    }
}