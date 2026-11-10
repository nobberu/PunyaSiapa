<?php

/** @var int $id */
/** @var array $postingan */
/** @var array $komentar */
/** @var bool $canComment */

$title     = $postingan['nama_barang'] . ' - PunyaSiapa';
$styles    = ['pages/detail'];
$base      = BASE_PATH . '/kehilangan';
$canManage = Auth::check()
    && ((int) $postingan['id_pengguna'] === (int) Auth::id() || Auth::isAdmin());

$status      = (string) $postingan['status_barang'];
$statusLabel = $status === 'selesai' ? 'Selesai' : 'Aktif';
$statusClass = $status === 'selesai' ? 'badge badge-selesai' : 'badge badge-aktif';
?>

<main class="detail" id="detail-page"
    data-base="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>"
    data-root="<?= htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') ?>"
    data-id="<?= (int) $id ?>"
    data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

    <a class="back-link" href="<?= $base ?>">&larr; Kembali ke daftar</a>

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
                    <dt>Lokasi</dt>
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

            <form class="inline-form" method="post"
                action="<?= $base ?>/<?= (int) $id ?>/delete"
                data-confirm="Hapus laporan ini? Tindakan ini tidak bisa dibatalkan."
                data-confirm-label="Ya, hapus">
                <input type="hidden" name="_csrf"
                    value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-danger" type="submit">Hapus laporan</button>
            </form>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/../partials/diskusi.php'; ?>
</main>

<script src="<?= BASE_PATH ?>/js/detail.js" defer></script>
