<?php

$jumlahKomentar = count($komentar);
?>

<section class="card diskusi" aria-labelledby="diskusi-title">
    <header class="diskusi-header">
        <h2 id="diskusi-title">Diskusi</h2>
        <span class="diskusi-count" id="diskusi-count"><?= $jumlahKomentar ?> komentar</span>
    </header>

    <div id="daftar-komentar" class="komentar">
        <?php if (empty($komentar)): ?>
            <p class="empty-state empty-state-inline" id="pesan-kosong">
                Belum ada diskusi untuk laporan ini.
            </p>
        <?php else: ?>
            <?php foreach ($komentar as $k): ?>
                <?php
                $canDelete = Auth::check()
                    && ((int) $k['id_pengguna'] === (int) Auth::id() || Auth::isAdmin());
                ?>
                <div class="komentar-item" id="komentar-<?= (int) $k['id_komentar'] ?>">
                    <span class="komentar-avatar" aria-hidden="true">
                        <?= htmlspecialchars(inisial($k['nama_pengguna'])) ?>
                    </span>

                    <div class="komentar-body">
                        <div class="komentar-meta">
                            <span class="komentar-name"><?= htmlspecialchars($k['nama_pengguna']) ?></span>
                            <span class="komentar-date">
                                <?= htmlspecialchars(tanggal_id($k['waktu_komentar'], true)) ?>
                            </span>
                        </div>
                        <p class="komentar-text"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></p>
                    </div>

                    <?php if ($canDelete): ?>
                        <form method="post"
                            action="<?= BASE_PATH ?>/komentar/<?= (int) $k['id_komentar'] ?>/delete"
                            class="inline-form komentar-delete"
                            data-ajax-confirm="Hapus komentar ini?">
                            <input type="hidden" name="_csrf"
                                value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn-text" type="submit">Hapus</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="composer">
        <form id="form-komentar" class="form composer-form" method="post"
            action="<?= $base ?>/<?= (int) $id ?>/komentar">
            <input type="hidden" name="_csrf"
                value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label class="field-label" for="input-komentar">
                    Tulis komentar
                    <?php if (!$canComment): ?>
                        <span class="field-hint" style="font-weight:400; margin-left:0.5rem;">(Klaim barang terlebih dahulu)</span>
                    <?php endif; ?>
                </label>
                <textarea id="input-komentar" name="isi_komentar" rows="3" required maxlength="1000"
                    placeholder="<?= $canComment
                        ? 'Tulis info atau pertanyaan untuk pelapor...'
                        : 'Klaim barang ini untuk mulai berdiskusi...' ?>"
                    data-counter="#komentar-count"
                    <?= $canComment ? '' : 'disabled' ?>></textarea>
                <div class="composer-footer">
                    <span class="field-hint">Maksimal 1000 karakter.</span>
                    <span class="composer-count" id="komentar-count">0/1000</span>
                </div>
            </div>

            <div class="form-actions">
                <?php if (!$canComment): ?>
                    <div class="claim-box" id="kotak-klaim" style="margin-bottom:0.75rem;">
                        <p><strong>Barang ini milik Anda?</strong> Ajukan klaim untuk membuka ruang diskusi dengan penemu.</p>
                        <button class="btn" type="button" id="btn-klaim">Klaim barang ini</button>
                    </div>
                <?php endif; ?>
                <button class="btn" type="submit" id="btn-kirim" <?= $canComment ? '' : 'disabled' ?>>Kirim komentar</button>
            </div>
        </form>
    </div>
</section>
