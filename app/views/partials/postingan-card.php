<?php
/** @var array  $p */
/** @var string $base */

$status      = (string) $p['status_barang'];
$statusLabel = $status === 'selesai' ? 'Selesai' : 'Aktif';
$statusClass = $status === 'selesai' ? 'badge badge-selesai' : 'badge badge-aktif';
$foto        = !empty($p['foto']) ? asset((string) $p['foto']) : null;
$showAuthor  = $showAuthor ?? true;
$isHidden    = (($p['status_tampil'] ?? 'tampil') === 'disembunyikan');
?>
<li class="postingan-card">
    <div class="postingan-card-media">
        <?php if ($foto !== null): ?>
            <img
                src="<?= htmlspecialchars($foto) ?>"
                alt="Foto <?= htmlspecialchars($p['nama_barang']) ?>"
                loading="lazy">
        <?php else: ?>
            <span class="postingan-card-placeholder">Tanpa foto</span>
        <?php endif; ?>
    </div>

    <div class="postingan-card-body">
        <?php if ($isHidden): ?>
            <span class="postingan-card-title">
                <?= htmlspecialchars($p['nama_barang']) ?>
            </span>
        <?php else: ?>
            <a class="postingan-card-title" href="<?= $base ?>/<?= (int) $p['id_postingan'] ?>">
                <?= htmlspecialchars($p['nama_barang']) ?>
            </a>
        <?php endif; ?>

        <p class="postingan-card-meta">
            <span><?= htmlspecialchars($p['lokasi_kejadian']) ?></span>
            <span aria-hidden="true">&middot;</span>
            <span><?= htmlspecialchars(tanggal_id($p['waktu_kejadian'])) ?></span>
        </p>

        <?php if ($showAuthor): ?>
            <p class="postingan-card-meta">
                <span>oleh <?= htmlspecialchars($p['nama_pembuat']) ?></span>
            </p>
        <?php endif; ?>

        <div class="postingan-card-chips">
            <?php if ($isHidden): ?>
                <span class="badge">Disembunyikan Admin</span>
            <?php endif; ?>
            <span class="chip"><?= htmlspecialchars($p['nama_kategori']) ?></span>
            <span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
        </div>
    </div>
</li>