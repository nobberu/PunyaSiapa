<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk mengelola tabel komentar postingan.
 */
class Komentar
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /**
     * Mengambil seluruh komentar aktif pada satu postingan tertentu,
     * diurutkan dari komentar terlama ke terbaru (kronologis).
     */
    public function forPostingan(int $idPostingan): array
    {
        $sql = "SELECT k.*, u.nama AS nama_pengguna, u.username
                FROM komentar k
                JOIN pengguna u ON u.id_pengguna = k.id_pengguna
                WHERE k.id_postingan = :id_postingan
                  AND k.status_tampil IS DISTINCT FROM 'dihapus'
                ORDER BY k.waktu_komentar ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_postingan' => $idPostingan]);

        return $stmt->fetchAll();
    }

    /**
     * Menyimpan komentar baru ke database.
     * Mengembalikan id_komentar yang baru dibuat.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO komentar 
                    (id_pengguna, id_postingan, isi_komentar, waktu_komentar, status_tampil)
                VALUES 
                    (:id_pengguna, :id_postingan, :isi_komentar, NOW(), 'tampil')
                RETURNING id_komentar";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'  => $data['id_pengguna'],
            'id_postingan' => $data['id_postingan'],
            'isi_komentar' => $data['isi_komentar'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Mencari data satu komentar berdasarkan ID.
     * Berguna untuk otorisasi cek pemilik komentar sebelum aksi hapus.
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT k.*, u.nama AS nama_pengguna, u.username
                FROM komentar k
                JOIN pengguna u ON u.id_pengguna = k.id_pengguna
                WHERE k.id_komentar = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Menyembunyikan komentar (Soft Delete) agar tidak tampil di publik.
     */
    public function softDelete(int $id): bool
    {
        $sql = "UPDATE komentar 
                SET status_tampil = 'dihapus' 
                WHERE id_komentar = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}