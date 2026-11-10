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
            $this->jsonResponse(false, 'Permintaan tidak valid. Muat ulang halaman.', 403);
        }

        $idPostingan = (int) $id;
        $postingan = (new Postingan())->find($idPostingan);

        if ($postingan === null || $postingan['status_tampil'] === 'dihapus') {
            $this->jsonResponse(false, 'Laporan tidak ditemukan.', 404);
        }

        $isiKomentar = trim((string) $r->post('isi_komentar'));

        // 2. Validasi input
        if ($isiKomentar === '') {
            $this->jsonResponse(false, 'Komentar tidak boleh kosong.', 422);
        }

        if (strlen($isiKomentar) > 1000) {
            $this->jsonResponse(false, 'Komentar maksimal 1000 karakter.', 422);
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

        $this->jsonResponse(true, 'Komentar terkirim.', 200, [
            'id_komentar'    => $idKomentarBaru,
            'nama_pengguna'  => $dataKomentar['nama_pengguna'] ?? 'Saya',
            'isi_komentar'   => $dataKomentar['isi_komentar'],
            'waktu_komentar' => tanggal_id($dataKomentar['waktu_komentar'] ?? null, true),
            'can_delete'     => true,
        ]);
    }

    /**
     * Menghapus komentar (Soft Delete).
     */
    public function destroy($id)
    {
        Auth::requireLogin();

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            $this->jsonResponse(false, 'Permintaan tidak valid.', 403);
        }

        $idKomentar = (int) $id;
        $komentarModel = new Komentar();
        $komentar = $komentarModel->find($idKomentar);

        if ($komentar === null || $komentar['status_tampil'] === 'dihapus') {
            $this->jsonResponse(false, 'Komentar tidak ditemukan.', 404);
        }

        // Hanya pembuat komentar itu sendiri ATAU admin yang boleh hapus
        if ((int) $komentar['id_pengguna'] !== (int) Auth::id() && !Auth::isAdmin()) {
            $this->jsonResponse(false, 'Anda tidak memiliki hak untuk menghapus komentar ini.', 403);
        }

        $komentarModel->softDelete($idKomentar);

        $this->jsonResponse(true, 'Komentar dihapus.', 200, [
            'id_komentar' => $idKomentar,
        ]);
    }

    /**
     * Kirim respon JSON dan exit.
     */
    private function jsonResponse(bool $success, string $message, int $statusCode = 200, ?array $data = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $payload = ['success' => $success, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        echo json_encode($payload);
        exit;
    }
}
