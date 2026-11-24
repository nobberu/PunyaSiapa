<?php

/**
 * Kontrak View (Detail & Aksi Moderasi Admin — WBS 3.9.1)
 * @var string     $jenis          'postingan' atau 'komentar'
 * @var int        $idTarget       ID postingan atau komentar
 * @var array      $konten         Record asli dari Postingan::find() atau Komentar::find()
 * @var array|null $postinganInduk Record postingan induk (khusus bila $jenis === 'komentar')
 * @var array      $laporan        Daftar seluruh laporan individual dari v_laporan_moderasi
 * @var array      $logs           Riwayat audit trail dari log_moderasi
 * @var bool       $adaPending     True bila masih ada laporan berstatus 'pending'
 * @var bool       $adaAktif       True bila masih ada laporan 'pending' atau 'reviewing'
 */

require_once __DIR__ . '/../../../models/Report.php';

$title       = 'Tinjau Moderasi - PunyaSiapa';
$styles      = ['pages/detail'];
$actionBase  = BASE_PATH . '/admin/moderasi/' . $jenis . '/' . (int) $idTarget;
$csrf        = htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8');
$statusTampil = (string) ($konten['status_tampil'] ?? 'tampil');

$flash = $_SESSION['flash_moderasi'] ?? null;
unset($_SESSION['flash_moderasi']);
?>

<main class="detail">
    <a class="back-link" href="<?= BASE_PATH ?>/admin/moderasi">&larr; Kembali ke daftar moderasi</a>

    <?php if ($flash !== null): ?>
        <div class="alert <?= ($flash['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?>" role="status">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <!-- 1. Konteks Konten yang Dilaporkan -->
    <section class="card" style="padding:1.25rem; margin-bottom:1rem;">
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <span class="chip">Target: <?= htmlspecialchars(ucfirst($jenis)) ?> #<?= (int) $idTarget ?></span>
            <span class="badge <?= $statusTampil === 'tampil' ? 'badge-aktif' : 'badge-selesai' ?>">
                Visibilitas: <?= htmlspecialchars(ucfirst($statusTampil)) ?>
            </span>
        </div>

        <?php if ($jenis === 'postingan'): ?>
            <?php if (!empty($konten['foto'])): ?>
                <img class="detail-foto"
                    src="<?= htmlspecialchars(asset((string) $konten['foto'])) ?>"
                    alt="Foto <?= htmlspecialchars((string) $konten['nama_barang']) ?>"
                    style="max-height:280px; object-fit:contain; margin-bottom:.75rem;">
            <?php endif; ?>

            <h2><?= htmlspecialchars((string) $konten['nama_barang']) ?></h2>
            <p class="field-hint">
                Pembuat: <?= htmlspecialchars((string) $konten['nama_pembuat']) ?>
                &middot; Kategori: <?= htmlspecialchars((string) $konten['nama_kategori']) ?>
                &middot; Jenis: <?= htmlspecialchars(ucfirst((string) $konten['jenis_postingan'])) ?>
                &middot; Status barang: <?= htmlspecialchars(ucfirst((string) $konten['status_barang'])) ?>
            </p>
            <p class="field-hint">
                Lokasi: <?= htmlspecialchars((string) $konten['lokasi_kejadian']) ?>
                &middot; Tanggal: <?= htmlspecialchars(tanggal_id($konten['waktu_kejadian'])) ?>
            </p>
            <p style="margin-top:.75rem;"><?= nl2br(htmlspecialchars((string) $konten['deskripsi'])) ?></p>
        <?php else: ?>
            <h2>Komentar oleh <?= htmlspecialchars((string) $konten['nama_pengguna']) ?></h2>
            <p class="field-hint">
                Waktu: <?= htmlspecialchars(tanggal_id($konten['waktu_komentar'], true)) ?>
                <?php if ($postinganInduk !== null): ?>
                    &middot; Pada postingan:
                    <strong><?= htmlspecialchars((string) $postinganInduk['nama_barang']) ?></strong>
                    (<?= htmlspecialchars(ucfirst((string) $postinganInduk['jenis_postingan'])) ?>)
                <?php endif; ?>
            </p>
            <blockquote style="margin:.75rem 0; padding:.75rem; border-left:3px solid currentColor;">
                <?= nl2br(htmlspecialchars((string) $konten['isi_komentar'])) ?>
            </blockquote>
        <?php endif; ?>
    </section>

    <!-- 2. Panel Tindakan Moderasi Admin -->
    <section class="card" style="padding:1.25rem; margin-bottom:1rem;">
        <h2>Tindakan Moderasi</h2>

        <?php if ($statusTampil === 'dihapus'): ?>
            <p class="field-hint">Konten ini telah dihapus sendiri oleh pemiliknya dan tidak dapat dipulihkan.</p>
        <?php endif; ?>

        <div style="display:flex; flex-direction:column; gap:.75rem; margin-top:.75rem;">
            <?php if ($adaPending): ?>
                <form method="post" action="<?= htmlspecialchars($actionBase . '/review') ?>">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <button class="btn btn-tonal btn-sm" type="submit">Tandai Sedang Ditinjau (Reviewing)</button>
                </form>
            <?php endif; ?>

            <?php if ($adaAktif): ?>
                <form method="post" action="<?= htmlspecialchars($actionBase . '/reject') ?>"
                      data-confirm="Tolak semua laporan aktif pada konten ini? Konten akan tetap tampil di publik."
                      data-confirm-label="Ya, tolak laporan"
                      style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="field" style="flex:1; min-width:220px; margin:0;">
                        <label class="field-label" for="alasan-reject">Catatan penolakan (opsional)</label>
                        <input id="alasan-reject" type="text" name="alasan_moderasi"
                            maxlength="<?= Report::MAX_ALASAN_LEN ?>"
                            placeholder="Contoh: Konten tidak melanggar ketentuan">
                    </div>
                    <button class="btn btn-outlined btn-sm" type="submit">Tolak Laporan (Reject)</button>
                </form>
            <?php endif; ?>

            <?php if ($statusTampil === 'tampil'): ?>
                <form method="post" action="<?= htmlspecialchars($actionBase . '/hide') ?>"
                      data-confirm="Sembunyikan konten ini dari publik dan tandai semua laporan aktif sebagai Selesai?"
                      data-confirm-label="Ya, sembunyikan"
                      style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="field" style="flex:1; min-width:220px; margin:0;">
                        <label class="field-label" for="alasan-hide">Alasan penyembunyian</label>
                        <input id="alasan-hide" type="text" name="alasan_moderasi"
                            maxlength="<?= Report::MAX_ALASAN_LEN ?>"
                            placeholder="Contoh: Terbukti spam / penipuan">
                    </div>
                    <button class="btn btn-danger btn-sm" type="submit">Sembunyikan Konten (Hide)</button>
                </form>
            <?php elseif ($statusTampil === 'disembunyikan'): ?>
                <form method="post" action="<?= htmlspecialchars($actionBase . '/restore') ?>"
                      data-confirm="Pulihkan konten ini agar tampil kembali di publik?"
                      data-confirm-label="Ya, pulihkan"
                      style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="field" style="flex:1; min-width:220px; margin:0;">
                        <label class="field-label" for="alasan-restore">Alasan pemulihan</label>
                        <input id="alasan-restore" type="text" name="alasan_moderasi"
                            maxlength="<?= Report::MAX_ALASAN_LEN ?>"
                            placeholder="Contoh: Setelah ditinjau ulang konten valid">
                    </div>
                    <button class="btn btn-sm" type="submit">Pulihkan Konten (Restore)</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3. Daftar Laporan Individual -->
    <section class="card" style="padding:1.25rem; margin-bottom:1rem;">
        <h2>Daftar Laporan Masuk (<?= count($laporan) ?>)</h2>
        <?php if (empty($laporan)): ?>
            <p class="field-hint">Belum ada laporan tercatat untuk konten ini.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:.75rem; margin-top:.75rem;">
                <?php foreach ($laporan as $item): ?>
                    <div style="padding:.75rem; border:1px solid rgba(0,0,0,.1); border-radius:.5rem;">
                        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
                            <div>
                                <strong>#<?= (int) $item['id_report'] ?></strong>
                                &middot; Pelapor: <?= htmlspecialchars((string) $item['nama_pelapor']) ?>
                                &middot; <span class="chip"><?= htmlspecialchars(Report::labelKategori((string) $item['kategori_report'])) ?></span>
                                &middot; <span class="badge badge-aktif"><?= htmlspecialchars(Report::labelStatus((string) $item['status_report'])) ?></span>
                            </div>
                            <small><?= htmlspecialchars(tanggal_id($item['waktu_report'], true)) ?></small>
                        </div>

                        <?php if (trim((string) $item['alasan_report']) !== ''): ?>
                            <p style="margin:.4rem 0 0;"><?= nl2br(htmlspecialchars((string) $item['alasan_report'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($item['nama_admin'])): ?>
                            <p class="field-hint" style="margin:.4rem 0 0;">
                                Ditinjau oleh <?= htmlspecialchars((string) $item['nama_admin']) ?>
                                pada <?= htmlspecialchars(tanggal_id($item['reviewed_at'], true)) ?>
                                <?php if (!empty($item['catatan_admin'])): ?>
                                    — Catatan: <?= htmlspecialchars((string) $item['catatan_admin']) ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. Riwayat Audit Trail (log_moderasi) -->
    <section class="card" style="padding:1.25rem;">
        <h2>Riwayat Tindakan (Audit Trail)</h2>
        <?php if (empty($logs)): ?>
            <p class="field-hint">Belum ada tindakan moderasi yang tercatat.</p>
        <?php else: ?>
            <ul style="margin:.75rem 0 0; padding-left:1.25rem;">
                <?php foreach ($logs as $log): ?>
                    <li style="margin-bottom:.4rem;">
                        <strong><?= htmlspecialchars(strtoupper((string) $log['jenis_aksi'])) ?></strong>
                        oleh <?= htmlspecialchars((string) $log['nama_admin']) ?>
                        (<?= htmlspecialchars(tanggal_id($log['waktu_aksi'], true)) ?>)
                        <?php if (!empty($log['status_sebelum']) || !empty($log['status_sesudah'])): ?>
                            [<?= htmlspecialchars((string) $log['status_sebelum']) ?> &rarr; <?= htmlspecialchars((string) $log['status_sesudah']) ?>]
                        <?php endif; ?>
                        <?php if (!empty($log['alasan'])): ?>
                            — <?= htmlspecialchars((string) $log['alasan']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>