<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Postingan.php';
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

/**
 * CRUD postingan untuk jenis "ditemukan".
 */
class DitemukanController
{
    private const JENIS = 'ditemukan';
    private const STATUS_BARANG = ['aktif', 'selesai'];

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

        [$data, $error] = $this->validate($r);

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

        echo View::render('ditemukan/[id]', [
            'id'        => (int) $id,
            'postingan' => $postingan,
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

        [$data, $error] = $this->validate($r);

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
     * Validasi input form.
     *
     * @return array{0:array,1:string|null} [data, pesan error]
     */
    private function validate(Request $r): array
    {
        $data = [
            'id_kategori'     => (int) $r->post('id_kategori'),
            'nama_barang'     => trim((string) $r->post('nama_barang')),
            'deskripsi'       => trim((string) $r->post('deskripsi')),
            'foto'            => trim((string) $r->post('foto')),
            'lokasi_kejadian' => trim((string) $r->post('lokasi_kejadian')),
            'waktu_kejadian'  => trim((string) $r->post('waktu_kejadian')),
            'status_barang'   => (string) $r->post('status_barang'),
        ];

        if (
            $data['nama_barang'] === '' ||
            $data['deskripsi'] === '' ||
            $data['foto'] === '' ||
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

        if ($this->len($data['foto']) > 255) {
            return [$data, 'Path foto maksimal 255 karakter.'];
        }

        if (!$this->isValidImagePath($data['foto'])) {
            return [$data, 'Foto harus berupa path relatif (/...) atau URL http(s).'];
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

        return [$data, null];
    }

    /** Path harus relatif ("/...") atau URL http(s). */
    private function isValidImagePath(string $foto): bool
    {
        return (bool) preg_match('#^(/[^\s]*|https?://[^\s]+)$#i', $foto);
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
