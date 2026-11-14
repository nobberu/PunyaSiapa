<?php

/** @var string $q */
/** @var string $jenis */
/** @var array  $hasil */

$title  = 'Hasil Pencarian — PunyaSiapa';
$styles = ['components/postingan', 'pages/list'];
$jumlah = count($hasil);
?>

<main class="list">
    <header class="list-header">
        <div>
            <h1 class="page-title">Hasil Pencarian</h1>
            <?php if ($q !== ''): ?>
                <p class="list-header-count">
                    <?= $jumlah ?> hasil untuk
                    "<strong><?= htmlspecialchars($q) ?></strong>"
                    <?php if ($jenis !== ''): ?>
                        · <?= htmlspecialchars(ucfirst($jenis)) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <!-- Form pencarian ulang -->
    <form method="get" action="<?= BASE_PATH ?>/search"
          style="margin-bottom:1.5rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <input
            type="search"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="Cari nama barang, deskripsi, lokasi..."
            class="field-input"
            style="flex:1; min-width:200px;">
        <select name="jenis" class="field-input" style="width:auto;">
            <option value=""           <?= $jenis === ''           ? 'selected' : '' ?>>Semua</option>
            <option value="ditemukan"  <?= $jenis === 'ditemukan'  ? 'selected' : '' ?>>Ditemukan</option>
            <option value="kehilangan" <?= $jenis === 'kehilangan' ? 'selected' : '' ?>>Kehilangan</option>
        </select>
        <button class="btn" type="submit">Cari</button>
    </form>

    <?php if ($q === ''): ?>
        <div class="empty-state">
            <p class="empty-state-title">Masukkan kata kunci</p>
            <p class="empty-state-text">Ketik nama barang, lokasi, atau deskripsi untuk mencari.</p>
        </div>
    <?php elseif (empty($hasil)): ?>
        <div class="empty-state">
            <p class="empty-state-title">Tidak ada hasil</p>
            <p class="empty-state-text">
                Tidak ditemukan laporan yang cocok dengan
                "<strong><?= htmlspecialchars($q) ?></strong>".
                Coba kata kunci lain.
            </p>
        </div>
    <?php else: ?>
        <ul class="postingan-grid">
            <?php foreach ($hasil as $p): ?>
                <?php
                $base = BASE_PATH . '/' . htmlspecialchars($p['jenis_postingan']);
                ?>
                <?php require __DIR__ . '/../partials/postingan-card.php'; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>