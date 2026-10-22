<?php

/** @var int $id */
/** @var array $postingan */

$title     = $postingan['nama_barang'] . ' - PunyaSiapa';
$base      = BASE_PATH . '/kehilangan';
$canManage = Auth::check()
    && ((int) $postingan['id_pengguna'] === (int) Auth::id() || Auth::isAdmin());
?>

<main class="detail">
    <p><a href="<?= $base ?>">&larr; Kembali</a></p>

    <h1><?= htmlspecialchars($postingan['nama_barang']) ?></h1>

    <?php if (!empty($postingan['foto'])): ?>
        <img class="detail-foto"
             src="<?= htmlspecialchars($postingan['foto']) ?>"
             alt="<?= htmlspecialchars($postingan['nama_barang']) ?>">
    <?php endif; ?>

    <dl class="detail-meta">
        <dt>Kategori</dt>
        <dd><?= htmlspecialchars($postingan['nama_kategori']) ?></dd>

        <dt>Status</dt>
        <dd><?= htmlspecialchars($postingan['status_barang']) ?></dd>

        <dt>Lokasi</dt>
        <dd><?= htmlspecialchars($postingan['lokasi_kejadian']) ?></dd>

        <dt>Tanggal</dt>
        <dd><?= htmlspecialchars($postingan['waktu_kejadian']) ?></dd>

        <dt>Pelapor</dt>
        <dd><?= htmlspecialchars($postingan['nama_pembuat']) ?></dd>
    </dl>

    <p class="detail-deskripsi"><?= nl2br(htmlspecialchars($postingan['deskripsi'])) ?></p>

    <?php if ($canManage): ?>
        <div class="actions">
            <a class="btn" href="<?= $base ?>/<?= (int) $id ?>/edit">Edit</a>

            <form method="post"
                  action="<?= $base ?>/<?= (int) $id ?>/delete"
                  onsubmit="return confirm('Hapus laporan ini?')">
                <input type="hidden" name="_csrf"
                       value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Hapus</button>
            </form>
        </div>
    <?php endif; ?>
</main>
