<?php

/** @var int $id */
/** @var array $postingan */
/** @var array $komentar */
/** @var bool $hasClaimed */
/** @var bool $canComment */

$title     = $postingan['nama_barang'] . ' - PunyaSiapa';
$base      = BASE_PATH . '/ditemukan';
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
    <hr style="margin: 24px 0;">

    <section class="diskusi">
        <h2>Diskusi Laporan</h2>

        <div id="daftar-komentar">
            <?php if (empty($komentar)): ?>
                <p id="pesan-kosong">Belum ada diskusi untuk laporan ini.</p>
            <?php else: ?>
                <?php foreach ($komentar as $k): ?>
                    <?php
                    $canDelete = Auth::check() && ((int) $k['id_pengguna'] === (int) Auth::id() || Auth::isAdmin());
                    ?>
                    <div class="item-komentar" id="komentar-<?= (int) $k['id_komentar'] ?>" style="margin-bottom: 12px; padding: 6px 0; border-bottom: 1px solid #ddd;">
                        <strong><?= htmlspecialchars($k['nama_pengguna']) ?></strong>
                        <small style="color: #666;">(<?= htmlspecialchars($k['waktu_komentar']) ?>)</small>
                        <p style="margin: 4px 0;"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></p>
                        <?php if ($canDelete): ?>
                            <form method="post" action="<?= BASE_PATH ?>/komentar/<?= (int) $k['id_komentar'] ?>/delete" class="form-hapus-komentar" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top: 16px;">
            <?php if ($canComment): ?>
                <form id="form-komentar" method="post" action="<?= $base ?>/<?= (int) $id ?>/komentar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <textarea name="isi_komentar" id="input-komentar" rows="3" required placeholder="Tulis pesan/tanya penemu..." style="width: 100%; max-width: 500px; display: block; margin-bottom: 8px;"></textarea>
                    <button type="submit" id="btn-kirim">Kirim Komentar</button>
                </form>
            <?php else: ?>
                <div id="kotak-klaim">
                    <p>Barang ini milik Anda? Ajukan klaim untuk membuka ruang diskusi dengan penemu.</p>
                    <button type="button" id="btn-klaim">Klaim Barang Ini</button>
                </div>
                <form id="form-komentar" method="post" action="<?= $base ?>/<?= (int) $id ?>/komentar" style="display: none;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <textarea name="isi_komentar" id="input-komentar" rows="3" required placeholder="Tulis pesan/tanya penemu..." style="width: 100%; max-width: 500px; display: block; margin-bottom: 8px;"></textarea>
                    <button type="submit" id="btn-kirim">Kirim Komentar</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>';
            const formKomentar = document.getElementById('form-komentar');
            const inputKomentar = document.getElementById('input-komentar');
            const btnKirim = document.getElementById('btn-kirim');
            const daftarKomentar = document.getElementById('daftar-komentar');
            const pesanKosong = document.getElementById('pesan-kosong');
            const btnKlaim = document.getElementById('btn-klaim');
            const kotakKlaim = document.getElementById('kotak-klaim');

            // 1. Logika Klik Tombol Klaim + Popup Konfirmasi
            if (btnKlaim) {
                btnKlaim.addEventListener('click', function() {
                    if (confirm('Apakah kamu benar-benar pemilik dari barang ini?')) {
                        const formData = new FormData();
                        formData.append('_csrf', csrfToken);

                        fetch('<?= $base ?>/<?= (int) $id ?>/claim', {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    alert(data.message);
                                    if (kotakKlaim) kotakKlaim.style.display = 'none';
                                    if (formKomentar) formKomentar.style.display = 'block';
                                } else {
                                    alert(data.message || 'Gagal mengajukan klaim.');
                                }
                            })
                            .catch(() => alert('Terjadi kesalahan koneksi.'));
                    }
                });
            }

            // 2. Logika Kirim Komentar Cepat (AJAX tanpa refresh)
            if (formKomentar) {
                formKomentar.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const isi = inputKomentar.value.trim();
                    if (!isi) return;

                    btnKirim.disabled = true;
                    btnKirim.textContent = 'Mengirim...';

                    const formData = new FormData(formKomentar);

                    fetch(formKomentar.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(res => {
                            btnKirim.disabled = false;
                            btnKirim.textContent = 'Kirim Komentar';

                            if (res.success) {
                                inputKomentar.value = '';
                                if (pesanKosong) pesanKosong.remove();

                                const div = document.createElement('div');
                                div.className = 'item-komentar';
                                div.id = 'komentar-' + res.data.id_komentar;
                                div.style = 'margin-bottom: 12px; padding: 6px 0; border-bottom: 1px solid #ddd;';
                                div.innerHTML = `
                            <strong>${res.data.nama_pengguna}</strong>
                            <small style="color: #666;">(${res.data.waktu_komentar})</small>
                            <p style="margin: 4px 0;">${res.data.isi_komentar}</p>
                            <form method="post" action="<?= BASE_PATH ?>/komentar/${res.data.id_komentar}/delete" class="form-hapus-komentar" style="display:inline;">
                                <input type="hidden" name="_csrf" value="${csrfToken}">
                                <button type="submit">Hapus</button>
                            </form>
                        `;
                                daftarKomentar.appendChild(div);
                            } else {
                                alert(res.message || 'Gagal mengirim komentar.');
                            }
                        })
                        .catch(() => {
                            btnKirim.disabled = false;
                            btnKirim.textContent = 'Kirim Komentar';
                            alert('Terjadi kesalahan jaringan.');
                        });
                });
            }

            // 3. Logika Hapus Pesan Sendiri (AJAX tanpa refresh)
            if (daftarKomentar) {
                daftarKomentar.addEventListener('submit', function(e) {
                    if (e.target && e.target.classList.contains('form-hapus-komentar')) {
                        e.preventDefault();
                        if (!confirm('Hapus komentar ini?')) return;

                        const formHapus = e.target;
                        const itemKomentar = formHapus.closest('.item-komentar');
                        const formData = new FormData(formHapus);

                        fetch(formHapus.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success && itemKomentar) {
                                    itemKomentar.remove();
                                } else {
                                    alert('Gagal menghapus komentar.');
                                }
                            })
                            .catch(() => alert('Terjadi kesalahan jaringan.'));
                    }
                });
            }
        });
    </script>
</main>