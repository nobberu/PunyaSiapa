<?php

/**
 * Kontrak View (Halaman Laporan Saya — WBS 3.8.1)
 * @var array $laporan Daftar baris dari v_laporan_moderasi milik user login
 */

require_once __DIR__ . '/../../models/Report.php';

$title  = 'Laporan Saya - PunyaSiapa';
$styles = ['pages/list'];
$jumlah = count($laporan);

$flash = $_SESSION['flash_report'] ?? null;
unset($_SESSION['flash_report']);
?>

<main class="list">
    <header class="list-header">
        <div>
            <h1 class="page-title">Laporan Saya</h1>
            <p class="list-header-count"><?= $jumlah ?> laporan dikirim</p>
        </div>
        <a class="btn btn-outlined" href="<?= BASE_PATH ?>/profil">&larr; Kembali ke profil</a>
    </header>

    <?php if ($flash !== null): ?>
        <div class="alert <?= ($flash['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?>" role="status">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($laporan)): ?>
        <div class="empty-state">
            <p class="empty-state-title">Belum ada laporan</p>
            <p class="empty-state-text">Laporan konten yang kamu kirim akan tercatat dan dapat dipantau statusnya di sini.</p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($laporan as $row): ?>
                <?php
                $status      = (string) $row['status_report'];
                $statusLabel = Report::labelStatus($status);
                $badgeClass  = ($status === 'resolved') ? 'badge badge-selesai' : 'badge badge-aktif';
                $targetUrl   = BASE_PATH . '/' . $row['jenis_postingan'] . '/' . (int) $row['id_postingan_induk'];
                ?>
                <article class="card" style="padding:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.5rem;">
                        <div>
                            <span class="chip"><?= htmlspecialchars(ucfirst((string) $row['jenis_target'])) ?></span>
                            <span class="chip"><?= htmlspecialchars(Report::labelKategori((string) $row['kategori_report'])) ?></span>
                            <span class="<?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                        </div>
                        <small><?= htmlspecialchars(tanggal_id($row['waktu_report'], true)) ?></small>
                    </div>

                    <p style="margin:.25rem 0;">
                        <strong>Target:</strong>
                        <?php if ($row['status_tampil_target'] === 'tampil'): ?>
                            <a href="<?= htmlspecialchars($targetUrl) ?>">
                                <?= htmlspecialchars((string) $row['ringkasan_target']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars((string) $row['ringkasan_target']) ?>
                            <em>(konten tidak lagi tampil di publik)</em>
                        <?php endif; ?>
                    </p>

                    <?php if (trim((string) $row['alasan_report']) !== ''): ?>
                        <p style="margin:.25rem 0;">
                            <strong>Keteranganmu:</strong>
                            <?= nl2br(htmlspecialchars((string) $row['alasan_report'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($status === 'resolved'): ?>
                        <p class="field-hint" style="margin-top:.5rem;">
                            Laporan yang kamu kirim telah diperiksa dan ditindaklanjuti oleh Admin.
                        </p>
                    <?php elseif ($status === 'rejected'): ?>
                        <p class="field-hint" style="margin-top:.5rem;">
                            Laporan yang kamu kirim telah diperiksa. Berdasarkan hasil peninjauan, konten tidak ditemukan melanggar ketentuan.
                        </p>
                    <?php elseif ($status === 'reviewing'): ?>
                        <p class="field-hint" style="margin-top:.5rem;">
                            Laporan kamu sedang ditinjau oleh Admin.
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>