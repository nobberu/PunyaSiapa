<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk mengelola tabel claim (pengajuan klaim, verifikasi bukti,
 * serta persetujuan/penolakan oleh penemu).
 */
class Claim
{
    public const STATUS = [
        'diajukan'  => 'Menunggu Verifikasi',
        'disetujui' => 'Disetujui',
        'ditolak'   => 'Ditolak',
    ];

    public const MIN_BUKTI_LEN = 10;
    public const MAX_BUKTI_LEN = 500;

    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public static function labelStatus(string $kode): string
    {
        return self::STATUS[$kode] ?? ucfirst($kode);
    }

    /** Ambil klaim terbaru milik satu user pada postingan tertentu (atau null). */
    public function findByUserAndPostingan(int $idPengguna, int $idPostingan): ?array
    {
        $sql = "SELECT *
                FROM claim
                WHERE id_pengguna = :id_pengguna
                  AND id_postingan = :id_postingan
                ORDER BY waktu_claim DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'  => $idPengguna,
            'id_postingan' => $idPostingan,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Cek apakah seorang user sudah pernah mengajukan klaim pada postingan ini. */
    public function hasUserClaimed(int $idPengguna, int $idPostingan): bool
    {
        return $this->findByUserAndPostingan($idPengguna, $idPostingan) !== null;
    }

    /** Ambil seluruh pengajuan klaim pada satu postingan (khusus untuk Penemu/Admin). */
    public function forPostingan(int $idPostingan): array
    {
        $sql = "SELECT c.*,
                       u.nama AS nama_pengklaim,
                       u.username,
                       u.nomor_kontak
                FROM claim c
                JOIN pengguna u ON u.id_pengguna = c.id_pengguna
                WHERE c.id_postingan = :id_postingan
                ORDER BY
                    CASE
                        WHEN c.status_claim = 'disetujui' THEN 1
                        WHEN c.status_claim = 'diajukan'  THEN 2
                        ELSE 3
                    END ASC,
                    c.waktu_claim DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_postingan' => $idPostingan]);

        return $stmt->fetchAll();
    }

    /** Simpan pengajuan klaim baru beserta bukti teks & foto bukti (opsional). */
    public function create(array $data): int
    {
        $sql = "INSERT INTO claim
                    (id_pengguna, id_postingan, bukti_claim, foto_bukti, status_claim, waktu_claim)
                VALUES
                    (:id_pengguna, :id_postingan, :bukti_claim, :foto_bukti, 'diajukan', NOW())
                RETURNING id_claim";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_pengguna'  => $data['id_pengguna'],
            'id_postingan' => $data['id_postingan'],
            'bukti_claim'  => $data['bukti_claim'],
            'foto_bukti'   => $data['foto_bukti'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Transaksi Atomik: Setujui 1 klaim -> tolak klaim lain yang masih 'diajukan'
     * -> ubah status_barang postingan menjadi 'selesai'.
     *
     * @return array{ok: bool, message: string}
     */
    public function approve(int $idClaim, int $idPostingan): array
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Kunci baris postingan agar tidak terjadi 2 approval bersamaan
            $postStmt = $this->pdo->prepare(
                "SELECT status_barang, status_tampil
                 FROM postingan
                 WHERE id_postingan = :id
                 FOR UPDATE"
            );
            $postStmt->execute(['id' => $idPostingan]);
            $post = $postStmt->fetch();

            if (!$post || $post['status_tampil'] !== 'tampil') {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Laporan tidak ditemukan atau tidak aktif.'];
            }

            if ($post['status_barang'] === 'selesai') {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Barang ini sudah berstatus Selesai.'];
            }

            // 2. Kunci & validasi baris klaim yang dipilih
            $claimStmt = $this->pdo->prepare(
                "SELECT status_claim
                 FROM claim
                 WHERE id_claim = :cid
                   AND id_postingan = :pid
                 FOR UPDATE"
            );
            $claimStmt->execute([
                'cid' => $idClaim,
                'pid' => $idPostingan,
            ]);
            $statusClaim = $claimStmt->fetchColumn();

            if ($statusClaim === false) {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Data klaim tidak ditemukan.'];
            }

            if ($statusClaim !== 'diajukan') {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Klaim ini sudah diproses sebelumnya.'];
            }

            // 3. Setujui klaim terpilih
            $approveStmt = $this->pdo->prepare(
                "UPDATE claim SET status_claim = 'disetujui' WHERE id_claim = :cid"
            );
            $approveStmt->execute(['cid' => $idClaim]);

            // 4. Tolak otomatis klaim lain yang masih 'diajukan' pada barang yang sama
            $rejectOthers = $this->pdo->prepare(
                "UPDATE claim
                 SET status_claim = 'ditolak'
                 WHERE id_postingan = :pid
                   AND id_claim <> :cid
                   AND status_claim = 'diajukan'"
            );
            $rejectOthers->execute([
                'pid' => $idPostingan,
                'cid' => $idClaim,
            ]);

            // 5. Ubah status barang menjadi 'selesai'
            $closePost = $this->pdo->prepare(
                "UPDATE postingan SET status_barang = 'selesai' WHERE id_postingan = :pid"
            );
            $closePost->execute(['pid' => $idPostingan]);

            $this->pdo->commit();
            return ['ok' => true, 'message' => 'Klaim disetujui! Status barang otomatis ditandai Selesai.'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Tolak satu pengajuan klaim ('diajukan' -> 'ditolak') tanpa menutup status barang. */
    public function reject(int $idClaim, int $idPostingan): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE claim
             SET status_claim = 'ditolak'
             WHERE id_claim = :cid
               AND id_postingan = :pid
               AND status_claim = 'diajukan'"
        );
        $stmt->execute([
            'cid' => $idClaim,
            'pid' => $idPostingan,
        ]);

        return $stmt->rowCount() > 0;
    }
}