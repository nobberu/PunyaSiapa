<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk tabel postingan.
 *
 * status_tampil bertipe enum stts_tampil ('tampil' | 'dihapus') dan boleh NULL,
 * jadi penyaringan memakai "IS DISTINCT FROM 'dihapus'" agar baris lama yang
 * masih NULL tetap ikut tampil.
 */
class Postingan
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /** Daftar postingan satu jenis (kehilangan/ditemukan), tanpa yang dihapus. */
    public function all(string $jenis): array
    {
        $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_pembuat
                FROM postingan p
                JOIN kategori k ON k.id_kategori = p.id_kategori
                JOIN pengguna u ON u.id_pengguna = p.id_pengguna
                WHERE p.jenis_postingan = :jenis
                  AND p.status_tampil IS DISTINCT FROM 'dihapus'
                ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['jenis' => $jenis]);

        return $stmt->fetchAll();
    }

    /** Ambil satu postingan berdasarkan id (dengan nama kategori & pembuat). */
    public function find(int $id)
    {
        $sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_pembuat
                FROM postingan p
                JOIN kategori k ON k.id_kategori = p.id_kategori
                JOIN pengguna u ON u.id_pengguna = p.id_pengguna
                WHERE p.id_postingan = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Simpan postingan baru. created_at diisi NOW() dan status_tampil = 'tampil'.
     *
     * @return int id_postingan yang baru dibuat
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO postingan
                    (id_pengguna, id_kategori, nama_barang, deskripsi, foto,
                     lokasi_kejadian, waktu_kejadian, jenis_postingan,
                     status_barang, created_at, status_tampil)
                VALUES
                    (:id_pengguna, :id_kategori, :nama_barang, :deskripsi, :foto,
                     :lokasi_kejadian, :waktu_kejadian, :jenis_postingan,
                     :status_barang, NOW(), 'tampil')
                RETURNING id_postingan";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'     => $data['id_pengguna'],
            'id_kategori'     => $data['id_kategori'],
            'nama_barang'     => $data['nama_barang'],
            'deskripsi'       => $data['deskripsi'],
            'foto'            => $data['foto'],
            'lokasi_kejadian' => $data['lokasi_kejadian'],
            'waktu_kejadian'  => $data['waktu_kejadian'],
            'jenis_postingan' => $data['jenis_postingan'],
            'status_barang'   => $data['status_barang'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** Perbarui postingan. jenis_postingan & status_tampil tidak diubah. */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE postingan SET
                    id_kategori     = :id_kategori,
                    nama_barang     = :nama_barang,
                    deskripsi       = :deskripsi,
                    foto            = :foto,
                    lokasi_kejadian = :lokasi_kejadian,
                    waktu_kejadian  = :waktu_kejadian,
                    status_barang   = :status_barang
                WHERE id_postingan = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id_kategori'     => $data['id_kategori'],
            'nama_barang'     => $data['nama_barang'],
            'deskripsi'       => $data['deskripsi'],
            'foto'            => $data['foto'],
            'lokasi_kejadian' => $data['lokasi_kejadian'],
            'waktu_kejadian'  => $data['waktu_kejadian'],
            'status_barang'   => $data['status_barang'],
            'id'              => $id,
        ]);
    }

    /** Soft delete: ubah status_tampil menjadi 'dihapus'. */
    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE postingan SET status_tampil = 'dihapus' WHERE id_postingan = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}
