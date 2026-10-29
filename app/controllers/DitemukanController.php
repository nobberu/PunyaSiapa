<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../models/Komentar.php';
require_once __DIR__ . '/../models/Claim.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

/**
 * CRUD postingan untuk jenis "ditemukan".
 */
class DitemukanController
{
    private const JENIS = 'ditemukan';
    private const FOLDER = 'penemuan';
    private const STATUS_BARANG = ['aktif', 'selesai'];
    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png'];

    public function index()
    {
        echo View::render('ditemukan/index', [
            'postingan' => (new Postingan())->all(self::JENIS),
        ]);
    }

    public function create()
    {
        Auth::requireLogin();

        echo View::render('ditemukan/form', [
            'mode'     => 'create',
            'data'     => null,
            'kategori' => (new Kategori())->all(),
            'error'    => null,
        ]);
    }

    public function store()
    {
        Auth::requireLogin();

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo View::render('ditemukan/form', [
                'mode'     => 'create',
                'data'     => null,
                'kategori' => (new Kategori())->all(),
                'error'    => 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.',
            ]);
            return;
        }

        [$data, $error] = $this->validate($r, true);

        if ($error !== null) {
            echo View::render('ditemukan/form', [
                'mode'     => 'create',
                'data'     => $data,
                'kategori' => (new Kategori())->all(),
                'error'    => $error,
            ]);
            return;
        }

        $id = (new Postingan())->create([
            'id_pengguna'     => Auth::id(),
            'id_kategori'     => $data['id_kategori'],
            'nama_barang'     => $data['nama_barang'],
            'deskripsi'       => $data['deskripsi'],
            'foto'            => $data['foto'],
            'lokasi_kejadian' => $data['lokasi_kejadian'],
            'waktu_kejadian'  => $data['waktu_kejadian'],
            'jenis_postingan' => self::JENIS,
            'status_barang'   => $data['status_barang'],
        ]);

        header('Location: ' . BASE_PATH . '/' . self::JENIS . '/' . $id);
        exit;
    }

    public function show($id)
    {
        Auth::requireLogin();

        $postingan = $this->findVisible($id);

        if ($postingan === null) {
            return;
        }

        $idInt = (int) $id;
        $userId = (int) Auth::id();
        $claimModel = new Claim();

        // Cek apakah user adalah pembuat laporan, admin, atau sudah klaim
        $hasClaimed = $claimModel->hasUserClaimed($userId, $idInt);
        $isAuthor = ((int) $postingan['id_pengguna'] === $userId);
        $canComment = ($isAuthor || Auth::isAdmin() || $hasClaimed);

        echo View::render('ditemukan/[id]', [
            'id'          => $idInt,
            'postingan'   => $postingan,
            'komentar'    => (new Komentar())->forPostingan($idInt),
            'hasClaimed'  => $hasClaimed,
            'canComment'  => $canComment,
        ]);
    }

    public function edit($id)
    {
        Auth::requireLogin();

        $postingan = $this->findOwned($id);

        if ($postingan === null) {
            return;
        }

        echo View::render('ditemukan/form', [
            'mode'     => 'edit',
            'data'     => $postingan,
            'kategori' => (new Kategori())->all(),
            'error'    => null,
        ]);
    }

    public function update($id)
    {
        Auth::requireLogin();

        $postingan = $this->findOwned($id);

        if ($postingan === null) {
            return;
        }

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.';
            return;
        }

        [$data, $error] = $this->validate($r, false, $postingan['foto']);

        if ($error !== null) {
            echo View::render('ditemukan/form', [
                'mode'     => 'edit',
                'data'     => array_merge($postingan, $data),
                'kategori' => (new Kategori())->all(),
                'error'    => $error,
            ]);
            return;
        }

        (new Postingan())->update((int) $id, [
            'id_kategori'     => $data['id_kategori'],
            'nama_barang'     => $data['nama_barang'],
            'deskripsi'       => $data['deskripsi'],
            'foto'            => $data['foto'],
            'lokasi_kejadian' => $data['lokasi_kejadian'],
            'waktu_kejadian'  => $data['waktu_kejadian'],
            'status_barang'   => $data['status_barang'],
        ]);

        header('Location: ' . BASE_PATH . '/' . self::JENIS . '/' . (int) $id);
        exit;
    }

    public function destroy($id)
    {
        Auth::requireLogin();

        $postingan = $this->findOwned($id);

        if ($postingan === null) {
            return;
        }

        $r = new Request();

        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.';
            return;
        }

        (new Postingan())->softDelete((int) $id);

        header('Location: ' . BASE_PATH . '/' . self::JENIS);
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* Helper                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Validasi input form dan upload foto.
     *
     * @return array{0:array,1:string|null} [data, pesan error]
     */
    private function validate(Request $r, bool $isCreate = true, ?string $currentPhoto = null): array
    {
        $data = [
            'id_kategori'     => (int) $r->post('id_kategori'),
            'nama_barang'     => trim((string) $r->post('nama_barang')),
            'deskripsi'       => trim((string) $r->post('deskripsi')),
            'lokasi_kejadian' => trim((string) $r->post('lokasi_kejadian')),
            'waktu_kejadian'  => trim((string) $r->post('waktu_kejadian')),
            'status_barang'   => (string) $r->post('status_barang'),
            'foto'            => $currentPhoto ?? (string) $r->post('foto_lama', ''),
        ];

        if (
            $data['nama_barang'] === '' ||
            $data['deskripsi'] === '' ||
            $data['lokasi_kejadian'] === '' ||
            $data['waktu_kejadian'] === ''
        ) {
            return [$data, 'Semua kolom wajib diisi.'];
        }

        if ($this->len($data['nama_barang']) > 100) {
            return [$data, 'Nama barang maksimal 100 karakter.'];
        }

        if ($this->len($data['lokasi_kejadian']) > 100) {
            return [$data, 'Lokasi maksimal 100 karakter.'];
        }

        $date = DateTime::createFromFormat('Y-m-d', $data['waktu_kejadian']);
        if ($date === false || $date->format('Y-m-d') !== $data['waktu_kejadian']) {
            return [$data, 'Tanggal kejadian tidak valid.'];
        }

        if (!in_array($data['status_barang'], self::STATUS_BARANG, true)) {
            return [$data, 'Status barang tidak valid.'];
        }

        if ($data['id_kategori'] <= 0 || !(new Kategori())->exists($data['id_kategori'])) {
            return [$data, 'Kategori tidak valid.'];
        }

        // Proses berkas upload foto
        [$photoPath, $photoError] = $this->handleUpload($r, $isCreate, $data['foto']);
        if ($photoError !== null) {
            return [$data, $photoError];
        }
        $data['foto'] = $photoPath;

        return [$data, null];
    }

    /**
     * Memvalidasi dan menyimpan berkas foto yang diunggah.
     *
     * @return array{0:?string, 1:?string} [path_relatif, pesan_error]
     */
    private function handleUpload(Request $r, bool $isRequired, ?string $fallbackPhoto): array
    {
        $file = $r->file('foto');

        // Tidak ada berkas baru yang dipilih
        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            if ($isRequired || empty($fallbackPhoto)) {
                return [null, 'Foto wajib diunggah.'];
            }
            return [$fallbackPhoto, null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Terjadi kesalahan saat mengunggah foto.'];
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return [null, 'Ukuran foto maksimal 2 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [null, 'Format foto hanya boleh JPG atau PNG.'];
        }

        // Validasi MIME riil berkas
        $mime = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        }

        if ($mime !== null && !in_array($mime, self::ALLOWED_MIMES, true)) {
            return [null, 'Berkas harus berupa gambar JPG atau PNG yang valid.'];
        }

        $uploadDir = __DIR__ . '/../../public/uploads/' . self::FOLDER;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $normalizedExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        $filename = self::JENIS . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $normalizedExt;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [null, 'Gagal menyimpan berkas foto ke server.'];
        }

        return ['/uploads/' . self::FOLDER . '/' . $filename, null];
    }

    private function len(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    /** Ambil postingan yang tampil (bukan 'dihapus' & jenis cocok) atau kirim 404. */
    private function findVisible($id): ?array
    {
        $postingan = (new Postingan())->find((int) $id);

        if (
            $postingan === null ||
            $postingan['jenis_postingan'] !== self::JENIS ||
            $postingan['status_tampil'] === 'dihapus'
        ) {
            http_response_code(404);
            echo '404 Not Found';
            return null;
        }

        return $postingan;
    }

    /** Ambil postingan yang boleh dikelola (pemilik/admin) atau kirim 404/403. */
    private function findOwned($id): ?array
    {
        $postingan = $this->findVisible($id);

        if ($postingan === null) {
            return null;
        }

        if ((int) $postingan['id_pengguna'] !== (int) Auth::id() && !Auth::isAdmin()) {
            http_response_code(403);
            echo '403 Forbidden';
            return null;
        }

        return $postingan;
    }
}
