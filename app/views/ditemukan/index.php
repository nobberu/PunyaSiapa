<?php

/** @var array $postingan */

$title = 'Barang Ditemukan - PunyaSiapa';
$base  = BASE_PATH . '/ditemukan';
?>

<main class="list">
    <div class="list-header">
        <h1>Barang Ditemukan</h1>

        <?php if (Auth::check()): ?>
            <a class="btn" href="<?= $base ?>/create">Buat laporan</a>
        <?php endif; ?>
    </div>

    <?php if (empty($postingan)): ?>
        <p class="empty">Belum ada laporan.</p>
    <?php else: ?>
        <ul class="postingan-list">
            <?php foreach ($postingan as $p): ?>
                <li class="postingan-card">
                    <a class="postingan-title"
                       href="<?= $base ?>/<?= (int) $p['id_postingan'] ?>">
                        <?= htmlspecialchars($p['nama_barang']) ?>
                    </a>

                    <p class="postingan-meta">
                        <?= htmlspecialchars($p['nama_kategori']) ?>
                        &middot; <?= htmlspecialchars($p['lokasi_kejadian']) ?>
                        &middot; <?= htmlspecialchars($p['waktu_kejadian']) ?>
                    </p>

                    <p class="postingan-meta">
                        Status: <?= htmlspecialchars($p['status_barang']) ?>
                        &middot; oleh <?= htmlspecialchars($p['nama_pembuat']) ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
