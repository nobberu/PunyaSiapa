<?php

$title       = 'Profil - PunyaSiapa';
$styles      = ['components/postingan', 'pages/profile'];
$peran       = ($pengguna['role'] ?? '') === 'admin' ? 'Admin' : 'Pengguna';

$kehilanganBase = BASE_PATH . '/kehilangan';
$ditemukanBase  = BASE_PATH . '/ditemukan';

$showAuthor = false;
?>

<main class="profile">
    <section class="card profile-hero" aria-labelledby="profil-nama">
        <div class="profile-hero-avatar" aria-hidden="true">
            <?= htmlspecialchars(inisial($pengguna['nama'])) ?>
        </div>

        <div class="profile-hero-identity">
            <span class="chip"><?= htmlspecialchars($peran) ?></span>
            <h1 id="profil-nama"><?= htmlspecialchars($pengguna['nama']) ?></h1>
            <p class="profile-hero-meta">
                <span>@<?= htmlspecialchars($pengguna['username']) ?></span>
                <span aria-hidden="true">&middot;</span>
                <span><?= htmlspecialchars($pengguna['nomor_kontak']) ?></span>
            </p>
        </div>

        <div class="profile-hero-actions">
            <a class="btn btn-tonal" href="<?= $kehilanganBase ?>/create">Lapor kehilangan</a>
            <a class="btn btn-outlined" href="<?= $ditemukanBase ?>/create">Lapor temuan</a>
        </div>
    </section>

    <section class="profile-stats" aria-label="Ringkasan aktivitas">
        <div class="card stat">
            <span class="stat-value"><?= (int) $statistik['laporan'] ?></span>
            <span class="stat-label">Laporan</span>
        </div>
        <div class="card stat">
            <span class="stat-value"><?= (int) $statistik['komentar'] ?></span>
            <span class="stat-label">Komentar</span>
        </div>
        <div class="card stat">
            <span class="stat-value"><?= (int) $statistik['klaim'] ?></span>
            <span class="stat-label">Klaim</span>
        </div>
    </section>

    <section class="profile-section" aria-labelledby="profil-kehilangan">
        <header class="profile-section-header">
            <h2 id="profil-kehilangan">Laporan kehilangan</h2>
            <span class="profile-section-count"><?= count($kehilangan) ?> laporan</span>
        </header>

        <?php if (empty($kehilangan)): ?>
            <p class="empty-state empty-state-inline">
                Kamu belum pernah melaporkan barang hilang.
                <a href="<?= $kehilanganBase ?>/create">Buat laporan sekarang</a>.
            </p>
        <?php else: ?>
            <?php $base = $kehilanganBase; ?>
            <ul class="postingan-grid">
                <?php foreach ($kehilangan as $p): ?>
                    <?php require __DIR__ . '/../partials/postingan-card.php'; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="profile-section" aria-labelledby="profil-ditemukan">
        <header class="profile-section-header">
            <h2 id="profil-ditemukan">Laporan temuan</h2>
            <span class="profile-section-count"><?= count($ditemukan) ?> laporan</span>
        </header>

        <?php if (empty($ditemukan)): ?>
            <p class="empty-state empty-state-inline">
                Kamu belum pernah melaporkan barang temuan.
                <a href="<?= $ditemukanBase ?>/create">Buat laporan sekarang</a>.
            </p>
        <?php else: ?>
            <?php $base = $ditemukanBase; ?>
            <ul class="postingan-grid">
                <?php foreach ($ditemukan as $p): ?>
                    <?php require __DIR__ . '/../partials/postingan-card.php'; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
