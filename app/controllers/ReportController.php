<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../models/Komentar.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

/**
 * Controller WBS 3.8.1: pengiriman laporan (postingan/komentar) oleh pengguna
 * serta halaman pemantauan "Laporan Saya".
 */
class ReportController
{
    /** Endpoint: POST /postingan/{id}/report */
    public function storePostingan($id)
    {
        $this->processReport('postingan', (int) $id);
    }

    /** Endpoint: POST /komentar/{id}/report */
    public function storeKomentar($id)
    {
        $this->processReport('komentar', (int) $id);
    }

    /** Endpoint: GET /laporan-saya */
    public function myReports()
    {
        Auth::requireLogin();

        $laporan = (new Report())->byReporter((int) Auth::id());

        echo View::render('report/index', [
            'laporan' => $laporan,
        ]);
    }

    /**
     * Alur terpusat pengiriman laporan untuk target 'postingan' maupun 'komentar'.
     */
    private function processReport(string $jenisTarget, int $idTarget): void
    {
        $r = new Request();

        // 1. Cek autentikasi (AJAX -> 401 JSON, non-AJAX -> redirect login)
        if (!Auth::check()) {
            if ($r->isAjax()) {
                $this->jsonResponse(false, 'Silakan masuk terlebih dahulu untuk mengirim laporan.', 401);
            }
            Auth::requireLogin();
        }

        // 2. Validasi token CSRF
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            $this->respondError($r, 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.', 403);
        }

        $userId = (int) Auth::id();

        // 3. Validasi keberadaan target, visibilitas 'tampil', dan bukan milik sendiri
        $targetError = $this->validateTarget($jenisTarget, $idTarget, $userId);
        if ($targetError !== null) {
            $this->respondError($r, $targetError['message'], $targetError['code']);
        }

        // 4. Validasi input kategori & keterangan tambahan
        $kategori = trim((string) $r->post('kategori_report', ''));
        $alasan   = trim((string) $r->post('alasan_report', ''));

        if (!array_key_exists($kategori, Report::KATEGORI)) {
            $this->respondError($r, 'Kategori laporan tidak valid.', 422);
        }

        if ($kategori === 'other' && $alasan === '') {
            $this->respondError($r, 'Keterangan tambahan wajib diisi untuk kategori Lainnya.', 422);
        }

        if ($this->len($alasan) > Report::MAX_ALASAN_LEN) {
            $this->respondError(
                $r,
                'Keterangan tambahan maksimal ' . Report::MAX_ALASAN_LEN . ' karakter.',
                422
            );
        }

        $reportModel = new Report();

        // 5. Cek apakah user sudah punya laporan aktif (pending/reviewing) pada target ini
        $existing = $reportModel->findActiveByUser($jenisTarget, $idTarget, $userId);
        if ($existing !== null) {
            $this->respondSuccess(
                $r,
                'Kamu sudah memiliki laporan aktif untuk konten ini (status: ' . Report::labelStatus($existing['status_report']) . ').',
                [
                    'duplicate' => true,
                    'report'    => $existing,
                ]
            );
        }

        // 6. Rate limiting sederhana (maks 5 laporan per 10 menit lintas target)
        if ($reportModel->countRecentByUser($userId) >= Report::RATE_LIMIT_MAX) {
            $this->respondError(
                $r,
                'Terlalu banyak laporan dalam waktu singkat. Coba lagi dalam beberapa menit.',
                429
            );
        }

        // 7. Simpan laporan ke database (aman terhadap race condition)
        try {
            $result = $reportModel->create($jenisTarget, $idTarget, $userId, $kategori, $alasan);
        } catch (Throwable $e) {
            $this->respondError($r, 'Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi.', 500);
        }

        if (!$result['created']) {
            $statusLabel = Report::labelStatus($result['report']['status_report'] ?? 'pending');
            $this->respondSuccess(
                $r,
                'Kamu sudah memiliki laporan aktif untuk konten ini (status: ' . $statusLabel . ').',
                [
                    'duplicate' => true,
                    'report'    => $result['report'],
                ]
            );
        }

        $this->respondSuccess(
            $r,
            'Laporan berhasil dikirim dan akan ditinjau oleh Admin.',
            [
                'duplicate' => false,
                'report'    => $result['report'],
            ],
            201
        );
    }

    /**
     * Memastikan target ada di database, berstatus 'tampil', dan bukan milik pelapor.
     *
     * @return array{code: int, message: string}|null
     */
    private function validateTarget(string $jenisTarget, int $idTarget, int $userId): ?array
    {
        if ($idTarget <= 0) {
            return ['code' => 404, 'message' => 'Konten tidak ditemukan atau tidak dapat dilaporkan.'];
        }

        if ($jenisTarget === 'postingan') {
            $postingan = (new Postingan())->find($idTarget);
            if ($postingan === null || $postingan['status_tampil'] !== 'tampil') {
                return ['code' => 404, 'message' => 'Postingan tidak ditemukan atau sudah tidak tersedia.'];
            }
            if ((int) $postingan['id_pengguna'] === $userId) {
                return ['code' => 403, 'message' => 'Kamu tidak dapat melaporkan postingan milikmu sendiri.'];
            }
            return null;
        }

        // Target komentar: cek komentar AND cek postingan induknya
        $komentar = (new Komentar())->find($idTarget);
        if ($komentar === null || $komentar['status_tampil'] !== 'tampil') {
            return ['code' => 404, 'message' => 'Komentar tidak ditemukan atau sudah tidak tersedia.'];
        }

        $postinganInduk = (new Postingan())->find((int) $komentar['id_postingan']);
        if ($postinganInduk === null || $postinganInduk['status_tampil'] !== 'tampil') {
            return ['code' => 404, 'message' => 'Postingan terkait sudah tidak tersedia.'];
        }

        if ((int) $komentar['id_pengguna'] === $userId) {
            return ['code' => 403, 'message' => 'Kamu tidak dapat melaporkan komentar milikmu sendiri.'];
        }

        return null;
    }

    /** Hitung panjang string aman meski ekstensi mbstring tidak aktif. */
    private function len(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    /** Respon sukses (JSON untuk AJAX, redirect ke /laporan-saya untuk form biasa). */
    private function respondSuccess(Request $r, string $message, array $data = [], int $statusCode = 200): void
    {
        if ($r->isAjax()) {
            $this->jsonResponse(true, $message, $statusCode, $data);
        }

        $_SESSION['flash_report'] = ['type' => 'success', 'message' => $message];
        header('Location: ' . BASE_PATH . '/laporan-saya');
        exit;
    }

    /** Respon gagal (JSON untuk AJAX, plain status code untuk form non-AJAX). */
    private function respondError(Request $r, string $message, int $statusCode): void
    {
        if ($r->isAjax()) {
            $this->jsonResponse(false, $message, $statusCode);
        }

        http_response_code($statusCode);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }

    /** Kirim payload JSON standar dan hentikan eksekusi. */
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