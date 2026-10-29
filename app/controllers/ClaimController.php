<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Claim.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class ClaimController
{
    public function store($id)
    {
        Auth::requireLogin();

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            if ($r->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
                exit;
            }
            echo 'Permintaan tidak valid.';
            return;
        }

        $idPostingan = (int) $id;
        $postingan = (new Postingan())->find($idPostingan);

        if ($postingan === null || $postingan['status_tampil'] === 'dihapus') {
            http_response_code(404);
            if ($r->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan.']);
                exit;
            }
            echo 'Laporan tidak ditemukan.';
            return;
        }

        $claimModel = new Claim();
        // Jika belum klaim, simpan klaim baru
        if (!$claimModel->hasUserClaimed((int) Auth::id(), $idPostingan)) {
            $claimModel->create([
                'id_pengguna'  => Auth::id(),
                'id_postingan' => $idPostingan,
                'bukti_claim'  => trim((string) $r->post('alasan_klaim', 'Klaim diajukan melalui ruang diskusi')),
            ]);
        }

        if ($r->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Klaim berhasil dicatat. Kotak diskusi sekarang terbuka!',
            ]);
            exit;
        }

        header('Location: ' . BASE_PATH . '/ditemukan/' . $idPostingan);
        exit;
    }
}
