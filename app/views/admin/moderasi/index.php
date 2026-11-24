<?php

/**
 * Kontrak View (Dashboard Admin Moderation — WBS 3.9.1)
 * @var string $status  Filter aktif: all | pending | reviewing | resolved | rejected
 * @var array  $antrean Daftar target terkelompok dari Report::groupedForAdmin()
 * @var array  $counts  Ringkasan jumlah per status dari Report::countsByStatus()
 */

require_once __DIR__ . '/../../../models/Report.php';

$title  = 'Moderasi Laporan - PunyaSiapa';
$styles = ['pages/list'];

$tabs = [
    'all'       => 'Semua',
    'pending'   => 'Pending',
    'reviewing' => 'Ditinjau',
    'resolved'  => 'Selesai',
    'rejected'  => 'Ditolak',
];
?>

<main class="list">
    <header class="list-header">
        <div>
            <h1 class="page-title">Moderasi Laporan</h1>
            <p class="list-header-count"><?= count($antrean) ?> target konten pada filter ini</p>
        </div>
    </header>

    <!-- Tab Filter Status -->
    <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
        <?php foreach ($tabs as $kode => $label): ?>
            <?php $isCurrent = ($status === $kode); ?>
            <a class="btn btn-sm <?= $isCurrent ? '' : 'btn-outlined' ?>"
               href="<?= BASE_PATH ?>/admin/moderasi?status=<?= urlencode($kode) ?>">
                <?= htmlspecialchars($label) ?> (<?= (int) ($counts[$kode] ?? 0) ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($antrean)): ?>
        <div class="empty-state">
            <p class="empty-state-title">Tidak ada laporan</p>
            <p class="empty-state-text">Belum ada laporan konten pada status ini.</p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($antrean as $row): ?>
                <?php
                $detailUrl   = BASE_PATH . '/admin/moderasi/' . $row['jenis_target'] . '/' . (int) $row['id_target'];
                $visibilitas = (string) $row['status_tampil_target'];
                $statusRingkas = (string) $row['status_ringkas'];

                $kategoriList = array_filter(array_map('trim', explode(',', (string) $row['daftar_kategori'])));
                $kategoriLabels = array_map([Report::class, 'labelKategori'], $kategoriList);
                ?>
                <article class="card" style="padding:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.5rem;">
                        <div>
                            <span class="chip"><?= htmlspecialchars(ucfirst((string) $row['jenis_target'])) ?></span>
                            <span class="badge <?= $statusRingkas === 'resolved' ? 'badge-selesai' : 'badge-aktif' ?>">
                                <?= htmlspecialchars(Report::labelStatus($statusRingkas)) ?>
                            </span>
                            <span class="chip">Konten: <?= htmlspecialchars(ucfirst($visibilitas)) ?></span>
                        </div>
                        <small>Terakhir: <?= htmlspecialchars(tanggal_id($row['laporan_terakhir'], true)) ?></small>
                    </div>

                    <p style="margin:.25rem 0;">
                        <strong><?= htmlspecialchars((string) $row['ringkasan_target']) ?></strong>
                    </p>
                    <p class="field-hint" style="margin:.25rem 0;">
                        Pemilik: <?= htmlspecialchars((string) $row['nama_pemilik_target']) ?>
                        &middot; <?= (int) $row['jumlah_pelapor'] ?> pelapor
                        (<?= (int) $row['total_laporan'] ?> laporan, <?= (int) $row['jumlah_aktif'] ?> aktif)
                    </p>
                    <p class="field-hint" style="margin:.25rem 0 .75rem;">
                        Kategori: <?= htmlspecialchars(implode(', ', $kategoriLabels)) ?>
                    </p>

                    <a class="btn btn-sm btn-tonal" href="<?= htmlspecialchars($detailUrl) ?>">
                        Tinjau &amp; Moderasi
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>