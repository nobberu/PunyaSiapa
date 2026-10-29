<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Komentar.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class KomentarController
{
    /**
     * Menyimpan komentar baru pada suatu postingan.
     */
    public function store($id)
    {
        Auth::requireLogin();

        $r = new Request();

        // 1. Validasi CSRF Token
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            return $this->respond($r, false, 'Permintaan tidak valid. Muat ulang halaman.', 403);
        }

        $idPostingan = (int) $id;
        $postingan = (new Postingan())->find($idPostingan);

        if ($postingan === null || $postingan['status_tampil'] === 'dihapus') {
            return $this->respond($r, false, 'Laporan tidak ditemukan.', 404);
        }

        $isiKomentar = trim((string) $r->post('isi_komentar'));

        // 2. Validasi input
        if ($isiKomentar === '') {
            return $this->respond($r, false, 'Komentar tidak boleh kosong.', 422, $postingan);
        }

        if (strlen($isiKomentar) > 1000) {
            return $this->respond($r, false, 'Komentar maksimal 1000 karakter.', 422, $postingan);
        }

        // 3. Simpan ke database
        $komentarModel = new Komentar();
        $idKomentarBaru = $komentarModel->create([
            'id_pengguna'  => Auth::id(),
            'id_postingan' => $idPostingan,
            'isi_komentar' => $isiKomentar,
        ]);

        // Ambil data komentar yang baru disimpan (lengkap dengan nama pengirim)
        $dataKomentar = $komentarModel->find($idKomentarBaru);

        // Jika request dari AJAX (JavaScript), kirim respon JSON agar browser bisa langsung nampilin
        if ($r->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data'    => [
                    'id_komentar'    => $idKomentarBaru,
                    'nama_pengguna'  => $dataKomentar['nama_pengguna'] ?? 'Saya',
                    'isi_komentar'   => htmlspecialchars($dataKomentar['isi_komentar'], ENT_QUOTES, 'UTF-8'),
                    'waktu_komentar' => date('Y-m-d H:i:s'),
                    'can_delete'     => true,
                ],
            ]);
            exit;
        }

        // Fallback jika tanpa AJAX (redirect standar)
        $this->redirectBack($postingan);
    }

    /**
     * Menghapus komentar (Soft Delete).
     */
    public function destroy($id)
    {
        Auth::requireLogin();

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            return $this->respond($r, false, 'Permintaan tidak valid.', 403);
        }

        $idKomentar = (int) $id;
        $komentarModel = new Komentar();
        $komentar = $komentarModel->find($idKomentar);

        if ($komentar === null || $komentar['status_tampil'] === 'dihapus') {
            return $this->respond($r, false, 'Komentar tidak ditemukan.', 404);
        }

        // Hanya pembuat komentar itu sendiri ATAU admin yang boleh hapus
        if ((int) $komentar['id_pengguna'] !== (int) Auth::id() && !Auth::isAdmin()) {
            return $this->respond($r, false, 'Anda tidak memiliki hak untuk menghapus komentar ini.', 403);
        }

        $komentarModel->softDelete($idKomentar);

        if ($r->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'     => true,
                'id_komentar' => $idKomentar,
            ]);
            exit;
        }

        $postingan = (new Postingan())->find((int) $komentar['id_postingan']);
        $this->redirectBack($postingan);
    }

    /**
     * Helper untuk menangani respon (JSON jika AJAX, redirect jika form biasa).
     */
    private function respond(Request $r, bool $success, string $message, int $statusCode = 200, ?array $postingan = null): void
    {
        http_response_code($statusCode);

        if ($r->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            exit;
        }

        if (!$success) {
            $_SESSION['error_komentar'] = $message;
        }

        $this->redirectBack($postingan);
    }

    private function redirectBack(?array $postingan): void
    {
        if ($postingan !== null) {
            header('Location: ' . BASE_PATH . '/' . $postingan['jenis_postingan'] . '/' . (int) $postingan['id_postingan']);
        } else {
            header('Location: ' . BASE_PATH . '/');
        }
        exit;
    }
}
