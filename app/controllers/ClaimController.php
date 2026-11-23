<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Claim.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

class ClaimController
{
    private const MAX_FILE_SIZE      = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const ALLOWED_MIMES      = ['image/jpeg', 'image/png'];

    /** Endpoint: POST /ditemukan/{id}/claim */
    public function store($id)
    {
        $r = new Request();

        if (!Auth::check()) {
            if ($r->isAjax()) {
                $this->jsonResponse(false, 'Silakan masuk terlebih dahulu untuk mengajukan klaim.', 401);
            }
            Auth::requireLogin();
        }

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            $this->respondError($r, 'Token CSRF tidak valid. Muat ulang halaman dan coba lagi.', 403);
        }

        $idPostingan = (int) $id;
        $userId      = (int) Auth::id();
        $postingan   = (new Postingan())->find($idPostingan);

        if (
            $postingan === null ||
            $postingan['jenis_postingan'] !== 'ditemukan' ||
            $postingan['status_tampil'] !== 'tampil'
        ) {
            $this->respondError($r, 'Laporan tidak ditemukan.', 404);
        }

        if ($postingan['status_barang'] === 'selesai') {
            $this->respondError($r, 'Barang ini sudah selesai / telah kembali ke pemiliknya.', 422);
        }

        if ((int) $postingan['id_pengguna'] === $userId) {
            $this->respondError($r, 'Kamu tidak dapat mengklaim laporan temuan milikmu sendiri.', 403);
        }

        $claimModel = new Claim();
        $existing   = $claimModel->findByUserAndPostingan($userId, $idPostingan);
        if ($existing !== null) {
            $statusLabel = Claim::labelStatus((string) $existing['status_claim']);
            $this->respondError(
                $r,
                'Kamu sudah pernah mengajukan klaim pada barang ini (status: ' . $statusLabel . ').',
                409
            );
        }

        $bukti = trim((string) $r->post('bukti_claim', ''));
        $len   = $this->len($bukti);
        if ($len < Claim::MIN_BUKTI_LEN) {
            $this->respondError(
                $r,
                'Tuliskan ciri khusus atau bukti kepemilikan minimal ' . Claim::MIN_BUKTI_LEN . ' karakter.',
                422
            );
        }

        if ($len > Claim::MAX_BUKTI_LEN) {
            $this->respondError(
                $r,
                'Bukti kepemilikan maksimal ' . Claim::MAX_BUKTI_LEN . ' karakter.',
                422
            );
        }

        [$fotoBukti, $uploadError] = $this->handleProofUpload($r, $idPostingan, $userId);
        if ($uploadError !== null) {
            $this->respondError($r, $uploadError, 422);
        }

        try {
            $idClaim = $claimModel->create([
                'id_pengguna'  => $userId,
                'id_postingan' => $idPostingan,
                'bukti_claim'  => $bukti,
                'foto_bukti'   => $fotoBukti,
            ]);
        } catch (Throwable $e) {
            $this->respondError($r, 'Gagal menyimpan klaim. Silakan coba lagi.', 500);
        }

        if ($r->isAjax()) {
            $this->jsonResponse(
                true,
                'Klaim & bukti kepemilikan berhasil dikirim ke penemu. Kotak diskusi sekarang terbuka!',
                201,
                [
                    'id_claim'     => $idClaim,
                    'status_claim' => 'diajukan',
                ]
            );
        }

        $this->redirectDetail($idPostingan, 'success', 'Klaim & bukti kepemilikan berhasil dikirim ke penemu.');
    }

    /** Endpoint: POST /ditemukan/{id}/claim/{claimId}/approve (khusus Penemu/Admin) */
    public function approve($id, $claimId)
    {
        Auth::requireLogin();

        $idPostingan = (int) $id;
        $idClaim     = (int) $claimId;

        $this->authorizeFinderAndCsrf($idPostingan);

        try {
            $result = (new Claim())->approve($idClaim, $idPostingan);
        } catch (Throwable $e) {
            $this->redirectDetail($idPostingan, 'error', 'Terjadi kesalahan saat menyetujui klaim.');
        }

        $this->redirectDetail($idPostingan, $result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Endpoint: POST /ditemukan/{id}/claim/{claimId}/reject (khusus Penemu/Admin) */
    public function reject($id, $claimId)
    {
        Auth::requireLogin();

        $idPostingan = (int) $id;
        $idClaim     = (int) $claimId;

        $this->authorizeFinderAndCsrf($idPostingan);

        $ok = (new Claim())->reject($idClaim, $idPostingan);
        if (!$ok) {
            $this->redirectDetail($idPostingan, 'error', 'Klaim tidak ditemukan atau sudah diproses.');
        }

        $this->redirectDetail($idPostingan, 'success', 'Klaim ditolak. Barang tetap aktif untuk pemilik asli.');
    }

    /** Pastikan request punya CSRF valid dan user adalah Penemu (pemilik postingan) atau Admin. */
    private function authorizeFinderAndCsrf(int $idPostingan): void
    {
        $r = new Request();
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.';
            exit;
        }

        $postingan = (new Postingan())->find($idPostingan);
        if (
            $postingan === null ||
            $postingan['jenis_postingan'] !== 'ditemukan' ||
            $postingan['status_tampil'] !== 'tampil'
        ) {
            http_response_code(404);
            echo '404 Not Found';
            exit;
        }

        if ((int) $postingan['id_pengguna'] !== (int) Auth::id() && !Auth::isAdmin()) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    /** Validasi & simpan foto bukti opsional (JPG/PNG, maks 2 MB, cek MIME riil). */
    private function handleProofUpload(Request $r, int $idPostingan, int $userId): array
    {
        $file = $r->file('foto_bukti');

        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Terjadi kesalahan saat mengunggah foto bukti.'];
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return [null, 'Ukuran foto bukti maksimal 2 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [null, 'Format foto bukti hanya boleh JPG atau PNG.'];
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

        $uploadDir = __DIR__ . '/../../public/uploads/klaim';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $normalizedExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        $filename      = 'klaim_' . $idPostingan . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $normalizedExt;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            return [null, 'Gagal menyimpan foto bukti ke server.'];
        }

        return ['/uploads/klaim/' . $filename, null];
    }

    private function len(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function redirectDetail(int $idPostingan, string $type, string $message): void
    {
        $_SESSION['flash_detail'] = ['type' => $type, 'message' => $message];
        header('Location: ' . BASE_PATH . '/ditemukan/' . $idPostingan);
        exit;
    }

    private function respondError(Request $r, string $message, int $statusCode): void
    {
        if ($r->isAjax()) {
            $this->jsonResponse(false, $message, $statusCode);
        }
        http_response_code($statusCode);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }

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