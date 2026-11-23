<?php

/**
 * Kontrak View (Mesin Pencari & Filter — WBS 3.4.1)
 * @var string $q
 * @var string $jenis
 * @var int    $kategori
 * @var string $status
 * @var array  $daftarKategori
 * @var bool   $adaFilter
 * @var array  $hasil
 */

$title  = 'Pencarian & Filter - PunyaSiapa';
$styles = ['components/postingan', 'pages/list'];
$jumlah = count($hasil);
?>

<main class="list">
    <header class="list-header">
        <div>
            <h1 class="page-title">Pencarian &amp; Filter</h1>
            <?php if ($adaFilter): ?>
                <p class="list-header-count">
                    <?= $jumlah ?> laporan ditemukan
                    <?php if ($q !== ''): ?>
                        untuk "<strong><?= htmlspecialchars($q) ?></strong>"
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <!-- Form pencarian & filter -->
    <form method="get" action="<?= BASE_PATH ?>/search"
          style="margin-bottom:1.5rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <input
            type="search"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="Cari nama barang, deskripsi, lokasi..."
            class="field-input"
            style="flex:1; min-width:200px;">

        <select name="jenis" class="field-input" style="width:auto;" aria-label="Filter jenis laporan">
            <option value=""           <?= $jenis === ''           ? 'selected' : '' ?>>Semua jenis</option>
            <option value="ditemukan"  <?= $jenis === 'ditemukan'  ? 'selected' : '' ?>>Ditemukan</option>
            <option value="kehilangan" <?= $jenis === 'kehilangan' ? 'selected' : '' ?>>Kehilangan</option>
        </select>

        <select name="kategori" class="field-input" style="width:auto;" aria-label="Filter kategori">
            <option value="0" <?= $kategori === 0 ? 'selected' : '' ?>>Semua kategori</option>
            <?php foreach ($daftarKategori as $kat): ?>
                <option value="<?= (int) $kat['id_kategori'] ?>"
                    <?= $kategori === (int) $kat['id_kategori'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $kat['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="field-input" style="width:auto;" aria-label="Filter status barang">
            <option value=""        <?= $status === ''        ? 'selected' : '' ?>>Semua status</option>
            <option value="aktif"   <?= $status === 'aktif'   ? 'selected' : '' ?>>Aktif</option>
            <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select>

        <button class="btn" type="submit">Cari</button>
        <?php if ($adaFilter): ?>
            <a class="btn btn-outlined" href="<?= BASE_PATH ?>/search">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (!$adaFilter): ?>
        <div class="empty-state">
            <p class="empty-state-title">Masukkan kata kunci atau pilih filter</p>
            <p class="empty-state-text">Ketik nama barang/lokasi, atau pilih jenis, kategori, dan status untuk menyaring laporan.</p>
        </div>
    <?php elseif (empty($hasil)): ?>
        <div class="empty-state">
            <p class="empty-state-title">Tidak ada hasil</p>
            <p class="empty-state-text">
                Tidak ditemukan laporan yang sesuai dengan pencarian atau filter yang dipilih.
            </p>
        </div>
    <?php else: ?>
        <ul class="postingan-grid">
            <?php foreach ($hasil as $p): ?>
                <?php
                $base = BASE_PATH . '/' . htmlspecialchars((string) $p['jenis_postingan']);
                ?>
                <?php require __DIR__ . '/../partials/postingan-card.php'; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>