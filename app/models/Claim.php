<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk mengelola tabel claim postingan barang.
 */
class Claim
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /**
     * Memeriksa apakah seorang user sudah pernah mengajukan klaim pada postingan ini.
     */
    public function hasUserClaimed(int $idPengguna, int $idPostingan): bool
    {
        $sql = "SELECT 1 FROM claim 
                WHERE id_pengguna = :id_pengguna 
                  AND id_postingan = :id_postingan 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'  => $idPengguna,
            'id_postingan' => $idPostingan,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Menyimpan klaim baru ke database.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO claim 
                    (id_pengguna, id_postingan, bukti_claim, status_claim, waktu_claim)
                VALUES 
                    (:id_pengguna, :id_postingan, :bukti_claim, 'diajukan', NOW())
                RETURNING id_claim";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'  => $data['id_pengguna'],
            'id_postingan' => $data['id_postingan'],
            'bukti_claim'  => $data['bukti_claim'] ?? 'Pengajuan klaim kepemilikan via diskusi',
        ]);

        return (int) $stmt->fetchColumn();
    }
}