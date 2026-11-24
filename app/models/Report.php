<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model terpusat untuk mengelola laporan konten (report_postingan & report_komentar)
 * serta transaksi moderasi Admin + audit trail (log_moderasi).
 */
class Report
{
    public const KATEGORI = [
        'spam'          => 'Spam',
        'fraud'         => 'Penipuan',
        'harassment'    => 'Pelecehan',
        'misleading'    => 'Informasi menyesatkan',
        'privacy'       => 'Pelanggaran privasi',
        'inappropriate' => 'Konten tidak pantas',
        'other'         => 'Lainnya',
    ];

    public const STATUS = [
        'pending'   => 'Pending',
        'reviewing' => 'Sedang Ditinjau',
        'resolved'  => 'Selesai (Ditindak)',
        'rejected'  => 'Ditolak',
    ];

    public const MAX_ALASAN_LEN   = 500;
    public const RATE_LIMIT_MAX   = 5;  // maks 5 laporan
    public const RATE_LIMIT_MENIT = 10; // dalam 10 menit

    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /** Helper label kategori untuk tampilan View. */
    public static function labelKategori(string $kode): string
    {
        return self::KATEGORI[$kode] ?? ucfirst($kode);
    }

    /** Helper label status untuk tampilan View. */
    public static function labelStatus(string $kode): string
    {
        return self::STATUS[$kode] ?? ucfirst($kode);
    }

    /**
     * Peta whitelist nama tabel & kolom untuk target 'postingan' atau 'komentar'.
     * Menjaga prinsip DRY sekaligus mencegah SQL injection pada identifier dinamis.
     */
    private function tableConfig(string $jenisTarget): array
    {
        if ($jenisTarget === 'postingan') {
            return [
                'table'         => 'report_postingan',
                'pk'            => 'id_report_postingan',
                'fk'            => 'id_postingan',
                'content_table' => 'postingan',
                'content_pk'    => 'id_postingan',
            ];
        }

        if ($jenisTarget === 'komentar') {
            return [
                'table'         => 'report_komentar',
                'pk'            => 'id_report_komentar',
                'fk'            => 'id_komentar',
                'content_table' => 'komentar',
                'content_pk'    => 'id_komentar',
            ];
        }

        throw new InvalidArgumentException('Jenis target tidak valid.');
    }

    // =========================================================================
    // BAGIAN 1: PENGGUNA (WBS 3.8.1 — Kirim & Lihat Laporan Saya)
    // =========================================================================

    /** Cek apakah user sudah memiliki laporan aktif (pending/reviewing) pada target ini. */
    public function findActiveByUser(string $jenisTarget, int $idTarget, int $idPengguna): ?array
    {
        $cfg = $this->tableConfig($jenisTarget);

        $sql = "SELECT {$cfg['pk']} AS id_report, kategori_report, alasan_report, status_report, waktu_report
                FROM {$cfg['table']}
                WHERE {$cfg['fk']} = :id_target
                  AND id_pengguna = :id_pengguna
                  AND status_report IN ('pending', 'reviewing')
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_target'   => $idTarget,
            'id_pengguna' => $idPengguna,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Hitung total laporan yang dikirim user dalam jendela waktu rate-limit (lintas 2 tabel). */
    public function countRecentByUser(int $idPengguna): int
    {
        $menit = self::RATE_LIMIT_MENIT;

        $sql = "SELECT (
                    (SELECT COUNT(*) FROM report_postingan
                     WHERE id_pengguna = :u_post
                       AND waktu_report >= NOW() - INTERVAL '{$menit} minutes')
                    +
                    (SELECT COUNT(*) FROM report_komentar
                     WHERE id_pengguna = :u_kom
                       AND waktu_report >= NOW() - INTERVAL '{$menit} minutes')
                ) AS total";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'u_post' => $idPengguna,
            'u_kom'  => $idPengguna,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Simpan laporan baru. Tahan terhadap race condition (klik ganda) berkat
     * Partial Unique Index di PostgreSQL (menangkap SQLSTATE 23505).
     *
     * @return array{created: bool, report: ?array}
     */
    public function create(
        string $jenisTarget,
        int $idTarget,
        int $idPengguna,
        string $kategori,
        string $alasan
    ): array {
        $cfg = $this->tableConfig($jenisTarget);

        $sql = "INSERT INTO {$cfg['table']}
                    ({$cfg['fk']}, id_pengguna, kategori_report, alasan_report, waktu_report, status_report, updated_at)
                VALUES
                    (:id_target, :id_pengguna, :kategori, :alasan, NOW(), 'pending', NOW())
                RETURNING {$cfg['pk']} AS id_report, kategori_report, alasan_report, status_report, waktu_report";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id_target'   => $idTarget,
                'id_pengguna' => $idPengguna,
                'kategori'    => $kategori,
                'alasan'      => $alasan,
            ]);

            return [
                'created' => true,
                'report'  => $stmt->fetch() ?: null,
            ];
        } catch (PDOException $e) {
            // 23505 = unique_violation pada Partial Unique Index laporan aktif
            if ((string) $e->getCode() === '23505') {
                return [
                    'created' => false,
                    'report'  => $this->findActiveByUser($jenisTarget, $idTarget, $idPengguna),
                ];
            }
            throw $e;
        }
    }

    /** Daftar seluruh laporan yang pernah dikirim oleh seorang pengguna (/laporan-saya). */
    public function byReporter(int $idPengguna): array
    {
        $sql = "SELECT *
                FROM v_laporan_moderasi
                WHERE id_pelapor = :id_pelapor
                ORDER BY waktu_report DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_pelapor' => $idPengguna]);

        return $stmt->fetchAll();
    }

    // =========================================================================
    // BAGIAN 2: ADMIN READ QUERIES (WBS 3.9.1 — Antrean, Filter, Detail, Log)
    // =========================================================================

    /**
     * Antrean laporan dikelompokkan per target konten menggunakan GROUP BY & HAVING.
     * Laporan yang masih memiliki status 'pending' selalu diurutkan paling atas.
     */
    public function groupedForAdmin(string $statusFilter = 'all'): array
    {
        $having = '';
        if ($statusFilter === 'pending') {
            $having = "HAVING BOOL_OR(status_report = 'pending')";
        } elseif ($statusFilter === 'reviewing') {
            $having = "HAVING NOT BOOL_OR(status_report = 'pending') AND BOOL_OR(status_report = 'reviewing')";
        } elseif ($statusFilter === 'resolved') {
            $having = "HAVING NOT BOOL_OR(status_report IN ('pending', 'reviewing')) AND BOOL_OR(status_report = 'resolved')";
        } elseif ($statusFilter === 'rejected') {
            $having = "HAVING NOT BOOL_OR(status_report IN ('pending', 'reviewing', 'resolved')) AND BOOL_OR(status_report = 'rejected')";
        }

        $sql = "SELECT
                    jenis_target,
                    id_target,
                    id_postingan_induk,
                    jenis_postingan,
                    ringkasan_target,
                    status_tampil_target,
                    id_pemilik_target,
                    nama_pemilik_target,
                    COUNT(*)::INT                                                   AS total_laporan,
                    COUNT(DISTINCT id_pelapor)::INT                                 AS jumlah_pelapor,
                    COUNT(*) FILTER (WHERE status_report IN ('pending', 'reviewing'))::INT AS jumlah_aktif,
                    STRING_AGG(DISTINCT kategori_report, ', ' ORDER BY kategori_report)    AS daftar_kategori,
                    MAX(waktu_report)                                               AS laporan_terakhir,
                    CASE
                        WHEN BOOL_OR(status_report = 'pending')   THEN 'pending'
                        WHEN BOOL_OR(status_report = 'reviewing') THEN 'reviewing'
                        WHEN BOOL_OR(status_report = 'resolved')  THEN 'resolved'
                        ELSE 'rejected'
                    END AS status_ringkas
                FROM v_laporan_moderasi
                GROUP BY
                    jenis_target,
                    id_target,
                    id_postingan_induk,
                    jenis_postingan,
                    ringkasan_target,
                    status_tampil_target,
                    id_pemilik_target,
                    nama_pemilik_target
                {$having}
                ORDER BY
                    CASE
                        WHEN BOOL_OR(status_report = 'pending')   THEN 1
                        WHEN BOOL_OR(status_report = 'reviewing') THEN 2
                        ELSE 3
                    END ASC,
                    MAX(waktu_report) DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    /** Ringkasan jumlah kelompok target per status untuk tab filter dashboard Admin. */
    public function countsByStatus(): array
    {
        $sql = "WITH ringkasan_target AS (
                    SELECT
                        jenis_target,
                        id_target,
                        CASE
                            WHEN BOOL_OR(status_report = 'pending')   THEN 'pending'
                            WHEN BOOL_OR(status_report = 'reviewing') THEN 'reviewing'
                            WHEN BOOL_OR(status_report = 'resolved')  THEN 'resolved'
                            ELSE 'rejected'
                        END AS status_ringkas
                    FROM v_laporan_moderasi
                    GROUP BY jenis_target, id_target
                )
                SELECT status_ringkas, COUNT(*)::INT AS jumlah
                FROM ringkasan_target
                GROUP BY status_ringkas";

        $counts = [
            'all'       => 0,
            'pending'   => 0,
            'reviewing' => 0,
            'resolved'  => 0,
            'rejected'  => 0,
        ];

        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            $st = (string) $row['status_ringkas'];
            $n  = (int) $row['jumlah'];
            if (isset($counts[$st])) {
                $counts[$st] = $n;
            }
            $counts['all'] += $n;
        }

        return $counts;
    }

    /** Daftar seluruh laporan individual untuk satu target konten tertentu. */
    public function reportsForTarget(string $jenisTarget, int $idTarget): array
    {
        $this->tableConfig($jenisTarget); // validasi whitelist

        $sql = "SELECT *
                FROM v_laporan_moderasi
                WHERE jenis_target = :jenis_target
                  AND id_target = :id_target
                ORDER BY waktu_report DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'jenis_target' => $jenisTarget,
            'id_target'    => $idTarget,
        ]);

        return $stmt->fetchAll();
    }

    /** Riwayat audit trail moderasi untuk satu target konten. */
    public function logsForTarget(string $jenisTarget, int $idTarget): array
    {
        $this->tableConfig($jenisTarget); // validasi whitelist

        $sql = "SELECT l.*, u.nama AS nama_admin
                FROM log_moderasi l
                JOIN pengguna u ON u.id_pengguna = l.id_admin
                WHERE l.jenis_target = :jenis_target
                  AND l.id_target = :id_target
                ORDER BY l.waktu_aksi DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'jenis_target' => $jenisTarget,
            'id_target'    => $idTarget,
        ]);

        return $stmt->fetchAll();
    }

    // =========================================================================
    // BAGIAN 3: ADMIN MODERATION ACTIONS (Transaksional + Audit Trail)
    // =========================================================================

    /** Helper internal: tulis 1 baris riwayat ke tabel log_moderasi. */
    private function writeLog(
        int $idAdmin,
        string $jenisTarget,
        int $idTarget,
        string $jenisAksi,
        string $alasan,
        ?string $statusSebelum,
        ?string $statusSesudah
    ): void {
        $sql = "INSERT INTO log_moderasi
                    (id_admin, jenis_target, id_target, jenis_aksi, alasan, status_sebelum, status_sesudah, waktu_aksi)
                VALUES
                    (:id_admin, :jenis_target, :id_target, :jenis_aksi, :alasan, :status_sebelum, :status_sesudah, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_admin'       => $idAdmin,
            'jenis_target'   => $jenisTarget,
            'id_target'      => $idTarget,
            'jenis_aksi'     => $jenisAksi,
            'alasan'         => $alasan !== '' ? $alasan : null,
            'status_sebelum' => $statusSebelum,
            'status_sesudah' => $statusSesudah,
        ]);
    }

    /**
     * Transisi: pending -> reviewing untuk semua laporan pending pada target ini.
     * Mengembalikan jumlah baris laporan yang diperbarui.
     */
    public function markReviewing(string $jenisTarget, int $idTarget, int $idAdmin): int
    {
        $cfg = $this->tableConfig($jenisTarget);

        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE {$cfg['table']}
                    SET status_report = 'reviewing',
                        reviewed_by   = :id_admin,
                        reviewed_at   = NOW(),
                        updated_at    = NOW()
                    WHERE {$cfg['fk']} = :id_target
                      AND status_report = 'pending'";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id_admin'  => $idAdmin,
                'id_target' => $idTarget,
            ]);

            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $this->writeLog($idAdmin, $jenisTarget, $idTarget, 'review', 'Mulai peninjauan laporan', 'pending', 'reviewing');
            }

            $this->pdo->commit();
            return $affected;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Tolak seluruh laporan aktif (pending/reviewing -> rejected) tanpa menyembunyikan konten.
     */
    public function rejectActive(string $jenisTarget, int $idTarget, int $idAdmin, string $catatan = ''): int
    {
        $cfg = $this->tableConfig($jenisTarget);

        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE {$cfg['table']}
                    SET status_report = 'rejected',
                        reviewed_by   = :id_admin,
                        reviewed_at   = NOW(),
                        catatan_admin = :catatan,
                        updated_at    = NOW()
                    WHERE {$cfg['fk']} = :id_target
                      AND status_report IN ('pending', 'reviewing')";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id_admin'  => $idAdmin,
                'catatan'   => $catatan !== '' ? $catatan : null,
                'id_target' => $idTarget,
            ]);

            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $this->writeLog($idAdmin, $jenisTarget, $idTarget, 'reject', $catatan, 'aktif', 'rejected');
            }

            $this->pdo->commit();
            return $affected;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Transaksi Atomik: Sembunyikan konten ('disembunyikan') + tutup semua laporan aktif ('resolved') + catat log.
     *
     * @return array{ok: bool, message: string, resolved_count: int}
     */
    public function hideTargetAndResolve(string $jenisTarget, int $idTarget, int $idAdmin, string $alasan = ''): array
    {
        $cfg = $this->tableConfig($jenisTarget);

        try {
            $this->pdo->beginTransaction();

            // 1. Kunci baris konten (FOR UPDATE) agar konsisten bila ada 2 aksi bersamaan
            $lockStmt = $this->pdo->prepare(
                "SELECT status_tampil FROM {$cfg['content_table']} WHERE {$cfg['content_pk']} = :id FOR UPDATE"
            );
            $lockStmt->execute(['id' => $idTarget]);
            $currentStatus = $lockStmt->fetchColumn();

            if ($currentStatus === false) {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Konten tidak ditemukan.', 'resolved_count' => 0];
            }

            if ($currentStatus === 'dihapus') {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Konten sudah dihapus oleh pemiliknya.', 'resolved_count' => 0];
            }

            // 2. Ubah status visibilitas konten menjadi 'disembunyikan' (bila belum)
            if ($currentStatus !== 'disembunyikan') {
                $hideStmt = $this->pdo->prepare(
                    "UPDATE {$cfg['content_table']} SET status_tampil = 'disembunyikan' WHERE {$cfg['content_pk']} = :id"
                );
                $hideStmt->execute(['id' => $idTarget]);
            }

            // 3. Tutup otomatis semua laporan aktif pada target yang sama -> 'resolved'
            $resStmt = $this->pdo->prepare(
                "UPDATE {$cfg['table']}
                 SET status_report = 'resolved',
                     reviewed_by   = :id_admin,
                     reviewed_at   = NOW(),
                     catatan_admin = :catatan,
                     updated_at    = NOW()
                 WHERE {$cfg['fk']} = :id_target
                   AND status_report IN ('pending', 'reviewing')"
            );
            $resStmt->execute([
                'id_admin'  => $idAdmin,
                'catatan'   => $alasan !== '' ? $alasan : null,
                'id_target' => $idTarget,
            ]);
            $resolvedCount = $resStmt->rowCount();

            if ($currentStatus === 'disembunyikan' && $resolvedCount === 0) {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Konten sudah disembunyikan dan tidak ada laporan aktif.', 'resolved_count' => 0];
            }

            // 4. Catat ke audit trail
            $this->writeLog($idAdmin, $jenisTarget, $idTarget, 'hide', $alasan, (string) $currentStatus, 'disembunyikan');

            $this->pdo->commit();
            return ['ok' => true, 'message' => 'Konten berhasil disembunyikan.', 'resolved_count' => $resolvedCount];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Transaksi Atomik: Pulihkan konten dari 'disembunyikan' -> 'tampil' + catat log.
     * Menolak memulihkan konten yang berstatus 'dihapus' oleh pemiliknya.
     *
     * @return array{ok: bool, message: string}
     */
    public function restoreTarget(string $jenisTarget, int $idTarget, int $idAdmin, string $alasan = ''): array
    {
        $cfg = $this->tableConfig($jenisTarget);

        try {
            $this->pdo->beginTransaction();

            $lockStmt = $this->pdo->prepare(
                "SELECT status_tampil FROM {$cfg['content_table']} WHERE {$cfg['content_pk']} = :id FOR UPDATE"
            );
            $lockStmt->execute(['id' => $idTarget]);
            $currentStatus = $lockStmt->fetchColumn();

            if ($currentStatus === false) {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Konten tidak ditemukan.'];
            }

            if ($currentStatus !== 'disembunyikan') {
                $this->pdo->rollBack();
                return ['ok' => false, 'message' => 'Hanya konten yang disembunyikan Admin yang dapat dipulihkan.'];
            }

            $restoreStmt = $this->pdo->prepare(
                "UPDATE {$cfg['content_table']} SET status_tampil = 'tampil' WHERE {$cfg['content_pk']} = :id"
            );
            $restoreStmt->execute(['id' => $idTarget]);

            $this->writeLog($idAdmin, $jenisTarget, $idTarget, 'restore', $alasan, 'disembunyikan', 'tampil');

            $this->pdo->commit();
            return ['ok' => true, 'message' => 'Konten berhasil dipulihkan ke publik.'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
