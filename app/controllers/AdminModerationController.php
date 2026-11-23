<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../models/Komentar.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

/**
 * Controller WBS 3.9.1: dashboard moderasi Admin, peninjauan konteks konten,
 * dan eksekusi tindakan moderasi (review, reject, hide, restore).
 */
class AdminModerationController
{
    private const ALLOWED_FILTERS = ['all', 'pending', 'reviewing', 'resolved', 'rejected'];
    private const ALLOWED_TARGETS = ['postingan', 'komentar'];

    /**
     * Constructor Guard: pastikan hanya role 'admin' yang dapat mengakses seluruh endpoint ini.
     */
    public function __construct()
    {
        Auth::requireAdmin();
    }

    /** Endpoint: GET /admin/moderasi?status=pending */
    public function index()
    {
        $r      = new Request();
        $status = (string) $r->query('status', 'all');

        if (!in_array($status, self::ALLOWED_FILTERS, true)) {
            $status = 'all';
        }

        $reportModel = new Report();

        echo View::render('admin/moderasi/index', [
            'status'  => $status,
            'antrean' => $reportModel->groupedForAdmin($status),
            'counts'  => $reportModel->countsByStatus(),
        ]);
    }

    /** Endpoint: GET /admin/moderasi/{jenis}/{id} */
    public function show($jenis, $id)
    {
        $jenis    = $this->assertTargetType((string) $jenis);
        $idTarget = (int) $id;

        // Admin membaca langsung lewat Model::find() tanpa filter 'tampil'
        // agar konten yang sudah 'disembunyikan' tetap bisa diperiksa & dipulihkan.
        $konten         = null;
        $postinganInduk = null;

        if ($jenis === 'postingan') {
            $konten = (new Postingan())->find($idTarget);
        } else {
            $konten = (new Komentar())->find($idTarget);
            if ($konten !== null) {
                $postinganInduk = (new Postingan())->find((int) $konten['id_postingan']);
            }
        }

        if ($konten === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $reportModel = new Report();
        $laporan     = $reportModel->reportsForTarget($jenis, $idTarget);
        $logs        = $reportModel->logsForTarget($jenis, $idTarget);

        $adaPending = false;
        $adaAktif   = false;
        foreach ($laporan as $item) {
            if ($item['status_report'] === 'pending') {
                $adaPending = true;
                $adaAktif   = true;
            } elseif ($item['status_report'] === 'reviewing') {
                $adaAktif = true;
            }
        }

        echo View::render('admin/moderasi/detail', [
            'jenis'          => $jenis,
            'idTarget'       => $idTarget,
            'konten'         => $konten,
            'postinganInduk' => $postinganInduk,
            'laporan'        => $laporan,
            'logs'           => $logs,
            'adaPending'     => $adaPending,
            'adaAktif'       => $adaAktif,
        ]);
    }

    /** Endpoint: POST /admin/moderasi/{jenis}/{id}/review */
    public function markReviewing($jenis, $id)
    {
        $jenis    = $this->assertTargetType((string) $jenis);
        $idTarget = (int) $id;
        $this->verifyCsrfOrAbort();

        try {
            $affected = (new Report())->markReviewing($jenis, $idTarget, (int) Auth::id());
        } catch (Throwable $e) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Gagal memperbarui status laporan.');
        }

        if ($affected === 0) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Tidak ada laporan berstatus Pending pada konten ini.');
        }

        $this->redirectDetail($jenis, $idTarget, 'success', $affected . ' laporan ditandai Sedang Ditinjau.');
    }

    /** Endpoint: POST /admin/moderasi/{jenis}/{id}/reject */
    public function reject($jenis, $id)
    {
        $jenis    = $this->assertTargetType((string) $jenis);
        $idTarget = (int) $id;
        $r        = $this->verifyCsrfOrAbort();
        $catatan  = $this->readAdminReason($r, $jenis, $idTarget);

        try {
            $affected = (new Report())->rejectActive($jenis, $idTarget, (int) Auth::id(), $catatan);
        } catch (Throwable $e) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Gagal menolak laporan. Silakan coba lagi.');
        }

        if ($affected === 0) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Tidak ada laporan aktif yang dapat ditolak.');
        }

        $this->redirectDetail($jenis, $idTarget, 'success', $affected . ' laporan ditolak. Konten tetap tampil di publik.');
    }

    /** Endpoint: POST /admin/moderasi/{jenis}/{id}/hide */
    public function hide($jenis, $id)
    {
        $jenis    = $this->assertTargetType((string) $jenis);
        $idTarget = (int) $id;
        $r        = $this->verifyCsrfOrAbort();
        $alasan   = $this->readAdminReason($r, $jenis, $idTarget);

        try {
            $result = (new Report())->hideTargetAndResolve($jenis, $idTarget, (int) Auth::id(), $alasan);
        } catch (Throwable $e) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Terjadi kesalahan saat menyembunyikan konten.');
        }

        if (!$result['ok']) {
            $this->redirectDetail($jenis, $idTarget, 'error', $result['message']);
        }

        $pesan = $result['message'];
        if ($result['resolved_count'] > 0) {
            $pesan .= ' ' . $result['resolved_count'] . ' laporan aktif otomatis ditandai Selesai.';
        }

        $this->redirectDetail($jenis, $idTarget, 'success', $pesan);
    }

    /** Endpoint: POST /admin/moderasi/{jenis}/{id}/restore */
    public function restore($jenis, $id)
    {
        $jenis    = $this->assertTargetType((string) $jenis);
        $idTarget = (int) $id;
        $r        = $this->verifyCsrfOrAbort();
        $alasan   = $this->readAdminReason($r, $jenis, $idTarget);

        try {
            $result = (new Report())->restoreTarget($jenis, $idTarget, (int) Auth::id(), $alasan);
        } catch (Throwable $e) {
            $this->redirectDetail($jenis, $idTarget, 'error', 'Terjadi kesalahan saat memulihkan konten.');
        }

        $this->redirectDetail($jenis, $idTarget, $result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Validasi whitelist jenis target ('postingan' / 'komentar'). */
    private function assertTargetType(string $jenis): string
    {
        if (!in_array($jenis, self::ALLOWED_TARGETS, true)) {
            http_response_code(404);
            echo '404 Not Found';
            exit;
        }
        return $jenis;
    }

    /** Validasi token CSRF pada seluruh endpoint POST moderasi. */
    private function verifyCsrfOrAbort(): Request
    {
        $r = new Request();
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.';
            exit;
        }
        return $r;
    }

    /** Ambil & validasi panjang alasan/catatan Admin (maks 500 karakter). */
    private function readAdminReason(Request $r, string $jenis, int $idTarget): string
    {
        $alasan = trim((string) $r->post('alasan_moderasi', ''));
        $len    = function_exists('mb_strlen') ? mb_strlen($alasan, 'UTF-8') : strlen($alasan);

        if ($len > Report::MAX_ALASAN_LEN) {
            $this->redirectDetail(
                $jenis,
                $idTarget,
                'error',
                'Catatan moderasi maksimal ' . Report::MAX_ALASAN_LEN . ' karakter.'
            );
        }

        return $alasan;
    }

    /** Pola PRG: simpan flash message ke session lalu redirect ke halaman detail moderasi. */
    private function redirectDetail(string $jenis, int $idTarget, string $type, string $message): void
    {
        $_SESSION['flash_moderasi'] = [
            'type'    => $type,
            'message' => $message,
        ];

        header('Location: ' . BASE_PATH . '/admin/moderasi/' . $jenis . '/' . $idTarget);
        exit;
    }
}