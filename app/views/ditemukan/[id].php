<?php

/**
 * Kontrak View (Detail Barang Ditemukan — WBS 3.3.1, 3.6.1, 3.7.1, 3.8.1)
 * @var int        $id
 * @var array      $postingan
 * @var array      $komentar
 * @var bool       $hasClaimed
 * @var array|null $userClaim
 * @var array      $daftarKlaim    Hanya terisi bila $canManageClaim === true (privat)
 * @var bool       $canManageClaim
 * @var bool       $isSelesai
 * @var bool       $canComment
 */

require_once __DIR__ . '/../../models/Claim.php';

$title     = $postingan['nama_barang'] . ' - PunyaSiapa';
$styles    = ['pages/detail'];
$base      = BASE_PATH . '/ditemukan';
$canManage = Auth::check()
    && ((int) $postingan['id_pengguna'] === (int) Auth::id() || Auth::isAdmin());

$isSelesai      = $isSelesai ?? ($postingan['status_barang'] === 'selesai');
$userClaim      = $userClaim ?? null;
$daftarKlaim    = $daftarKlaim ?? [];
$canManageClaim = $canManageClaim ?? $canManage;

$status      = (string) $postingan['status_barang'];
$statusLabel = $status === 'selesai' ? 'Selesai' : 'Aktif';
$statusClass = $status === 'selesai' ? 'badge badge-selesai' : 'badge badge-aktif';
$csrf        = htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8');

$flashDetail = $_SESSION['flash_detail'] ?? null;
unset($_SESSION['flash_detail']);
?>

<main class="detail" id="detail-page"
    data-base="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>"
    data-root="<?= htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') ?>"
    data-id="<?= (int) $id ?>"
    data-csrf="<?= $csrf ?>">

    <a class="back-link" href="<?= $base ?>">&larr; Kembali ke daftar</a>

    <?php if ($flashDetail !== null): ?>
        <div class="alert <?= ($flashDetail['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?>" role="status">
            <?= htmlspecialchars((string) ($flashDetail['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_komentar'])): ?>
        <div class="alert alert-error" role="alert" data-autohide="6000">
            <?= htmlspecialchars($_SESSION['error_komentar']) ?>
        </div>
        <?php unset($_SESSION['error_komentar']); ?>
    <?php endif; ?>

    <article class="card detail-hero">
        <?php if (!empty($postingan['foto'])): ?>
            <img class="detail-foto"
                src="<?= htmlspecialchars(asset((string) $postingan['foto'])) ?>"
                alt="Foto <?= htmlspecialchars($postingan['nama_barang']) ?>">
        <?php endif; ?>

        <div class="detail-hero-body">
            <div class="detail-chips">
                <span class="chip"><?= htmlspecialchars($postingan['nama_kategori']) ?></span>
                <span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
            </div>

            <h1><?= htmlspecialchars($postingan['nama_barang']) ?></h1>

            <dl class="meta-list">
                <div class="meta-list-item">
                    <dt>Lokasi penemuan</dt>
                    <dd><?= htmlspecialchars($postingan['lokasi_kejadian']) ?></dd>
                </div>
                <div class="meta-list-item">
                    <dt>Tanggal</dt>
                    <dd><?= htmlspecialchars(tanggal_id($postingan['waktu_kejadian'])) ?></dd>
                </div>
                <div class="meta-list-item">
                    <dt>Dilaporkan oleh</dt>
                    <dd><?= htmlspecialchars($postingan['nama_pembuat']) ?></dd>
                </div>
            </dl>

            <p class="detail-desc"><?= nl2br(htmlspecialchars($postingan['deskripsi'])) ?></p>
        </div>
    </article>

    <?php if ($canManage): ?>
        <div class="detail-actions">
            <a class="btn btn-tonal" href="<?= $base ?>/<?= (int) $id ?>/edit">Edit laporan</a>

            <?php if (!$isSelesai): ?>
                <form class="inline-form" method="post"
                    action="<?= $base ?>/<?= (int) $id ?>/selesai"
                    data-confirm="Tandai laporan ini sebagai Selesai? Ruang diskusi dan pengajuan klaim baru akan ditutup."
                    data-confirm-label="Ya, tandai selesai">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <button class="btn" type="submit">Tandai Selesai</button>
                </form>
            <?php endif; ?>

            <form class="inline-form" method="post"
                action="<?= $base ?>/<?= (int) $id ?>/delete"
                data-confirm="Hapus laporan ini? Tindakan ini tidak bisa dibatalkan."
                data-confirm-label="Ya, hapus">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button class="btn btn-danger" type="submit">Hapus laporan</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (Auth::check() && (int) $postingan['id_pengguna'] !== (int) Auth::id()): ?>
        <div class="detail-actions">
            <button class="btn btn-outlined btn-sm"
                type="button"
                data-report-trigger
                data-report-url="<?= BASE_PATH ?>/postingan/<?= (int) $id ?>/report"
                data-report-label="Postingan: <?= htmlspecialchars($postingan['nama_barang'], ENT_QUOTES, 'UTF-8') ?>">
                Laporkan postingan
            </button>
        </div>
    <?php endif; ?>

    <!-- Status Klaim Pribadi (hanya terlihat oleh pengklaim sendiri) -->
    <?php if (!$canManageClaim && $userClaim !== null): ?>
        <?php $stClaim = (string) $userClaim['status_claim']; ?>
        <section class="card" style="padding:1rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap;">
                <strong>Status Klaim Kamu</strong>
                <span class="badge <?= $stClaim === 'disetujui' ? 'badge-selesai' : 'badge-aktif' ?>">
                    <?= htmlspecialchars(Claim::labelStatus($stClaim)) ?>
                </span>
            </div>
            <?php if ($stClaim === 'diajukan'): ?>
                <p class="field-hint" style="margin:.4rem 0 0;">
                    Bukti kepemilikanmu telah dikirim secara privat ke penemu. Silakan gunakan kolom diskusi di bawah untuk koordinasi umum.
                </p>
            <?php elseif ($stClaim === 'disetujui'): ?>
                <p class="field-hint" style="margin:.4rem 0 0;">
                    Selamat! Klaim kamu disetujui oleh penemu. Silakan lakukan serah terima barang.
                </p>
            <?php else: ?>
                <p class="field-hint" style="margin:.4rem 0 0;">
                    Klaim kamu belum dapat disetujui oleh penemu berdasarkan verifikasi bukti.
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Panel Verifikasi Klaim (PRIVAT: hanya tampil untuk Penemu & Admin) -->
    <?php if ($canManageClaim): ?>
        <section class="card" style="padding:1.25rem; margin-bottom:1rem;" aria-labelledby="klaim-masuk-title">
            <h2 id="klaim-masuk-title">Pengajuan Klaim Masuk (<?= count($daftarKlaim) ?>)</h2>
            <p class="field-hint" style="margin-bottom:.75rem;">
                Bukti di bawah bersifat rahasia dan hanya terlihat oleh kamu (penemu) serta Admin.
            </p>

            <?php if (empty($daftarKlaim)): ?>
                <p class="empty-state empty-state-inline">Belum ada pengajuan klaim untuk barang ini.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:.75rem;">
                    <?php foreach ($daftarKlaim as $c): ?>
                        <?php $st = (string) $c['status_claim']; ?>
                        <div style="padding:.75rem; border:1px solid rgba(0,0,0,.1); border-radius:.5rem;">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
                                <div>
                                    <strong><?= htmlspecialchars((string) $c['nama_pengklaim']) ?></strong>
                                    &middot; <span><?= htmlspecialchars((string) $c['nomor_kontak']) ?></span>
                                    &middot; <span class="badge <?= $st === 'disetujui' ? 'badge-selesai' : 'badge-aktif' ?>">
                                        <?= htmlspecialchars(Claim::labelStatus($st)) ?>
                                    </span>
                                </div>
                                <small><?= htmlspecialchars(tanggal_id($c['waktu_claim'], true)) ?></small>
                            </div>

                            <p style="margin:.5rem 0;">
                                <strong>Bukti:</strong> <?= nl2br(htmlspecialchars((string) $c['bukti_claim'])) ?>
                            </p>

                            <?php if (!empty($c['foto_bukti'])): ?>
                                <p style="margin:.5rem 0;">
                                    <img src="<?= htmlspecialchars(asset((string) $c['foto_bukti'])) ?>"
                                         alt="Foto bukti dari <?= htmlspecialchars((string) $c['nama_pengklaim']) ?>"
                                         style="max-height:200px; border-radius:.5rem; object-fit:contain;">
                                </p>
                            <?php endif; ?>

                            <?php if ($st === 'diajukan' && !$isSelesai): ?>
                                <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.5rem;">
                                    <form class="inline-form" method="post"
                                        action="<?= $base ?>/<?= (int) $id ?>/claim/<?= (int) $c['id_claim'] ?>/approve"
                                        data-confirm="Setujui klaim dari <?= htmlspecialchars((string) $c['nama_pengklaim'], ENT_QUOTES, 'UTF-8') ?>? Klaim lain akan ditolak dan barang otomatis ditandai Selesai."
                                        data-confirm-label="Ya, setujui klaim">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <button class="btn btn-sm" type="submit">Setujui Klaim</button>
                                    </form>

                                    <form class="inline-form" method="post"
                                        action="<?= $base ?>/<?= (int) $id ?>/claim/<?= (int) $c['id_claim'] ?>/reject"
                                        data-confirm="Tolak klaim dari <?= htmlspecialchars((string) $c['nama_pengklaim'], ENT_QUOTES, 'UTF-8') ?>?"
                                        data-confirm-label="Ya, tolak">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <button class="btn btn-outlined btn-sm" type="submit">Tolak</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php require __DIR__ . '/../partials/diskusi.php'; ?>
    <?php require __DIR__ . '/../partials/report-modal.php'; ?>
    <?php if (!$canManageClaim && !$isSelesai && $userClaim === null): ?>
        <?php require __DIR__ . '/../partials/claim-modal.php'; ?>
    <?php endif; ?>
</main>

<script src="<?= BASE_PATH ?>/js/detail.js" defer></script>