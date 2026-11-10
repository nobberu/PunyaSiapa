<?php

/** @var array $postingan */

$title  = 'Barang Hilang - PunyaSiapa';
$styles = ['components/postingan', 'pages/list'];
$base   = BASE_PATH . '/kehilangan';
$jumlah = count($postingan);
?>

<main class="list">
    <header class="list-header">
        <div>
            <h1 class="page-title">Barang Hilang</h1>
            <p class="list-header-count"><?= $jumlah ?> laporan</p>
        </div>

        <?php if (Auth::check()): ?>
            <a class="btn" href="<?= $base ?>/create">Buat laporan</a>
        <?php else: ?>
            <a class="btn btn-tonal" href="<?= BASE_PATH ?>/login">Masuk untuk melapor</a>
        <?php endif; ?>
    </header>

    <?php if (empty($postingan)): ?>
        <div class="empty-state">
            <p class="empty-state-title">Belum ada laporan</p>
            <p class="empty-state-text">Jadi yang pertama melaporkan barang hilang di sekitar kampus.</p>

            <?php if (Auth::check()): ?>
                <a class="btn" href="<?= $base ?>/create">Buat laporan</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <ul class="postingan-grid">
            <?php foreach ($postingan as $p): ?>
                <?php require __DIR__ . '/../partials/postingan-card.php'; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
