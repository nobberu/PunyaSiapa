<?php

/** @var string $mode   'create' | 'edit' */
/** @var array|null $data */
/** @var array $kategori */
/** @var string|null $error */

$isEdit = ($mode === 'edit');
$data   = $data ?? [];
$base   = BASE_PATH . '/kehilangan';
$action = $isEdit ? $base . '/' . (int) ($data['id_postingan'] ?? 0) : $base;

$title = ($isEdit ? 'Edit' : 'Buat') . ' Laporan - PunyaSiapa';

$val = function (string $key) use ($data): string {
    return htmlspecialchars((string) ($data[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

$statuses = ['aktif' => 'Aktif', 'selesai' => 'Selesai'];
?>

<main class="auth">
    <h1><?= $isEdit ? 'Edit laporan' : 'Buat laporan' ?></h1>

    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="postingan-form">
        <input type="hidden" name="_csrf"
            value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

        <label>
            Nama barang
            <input type="text" name="nama_barang" maxlength="100" required
                value="<?= $val('nama_barang') ?>">
        </label>

        <label>
            Kategori
            <select name="id_kategori" required>
                <option value="">-- pilih kategori --</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= (int) $k['id_kategori'] ?>"
                        <?= ((int) ($data['id_kategori'] ?? 0) === (int) $k['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Status barang
            <select name="status_barang" required>
                <?php foreach ($statuses as $value => $text): ?>
                    <option value="<?= $value ?>"
                        <?= (($data['status_barang'] ?? '') === $value) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($text) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Lokasi kejadian
            <input type="text" name="lokasi_kejadian" maxlength="100" required
                value="<?= $val('lokasi_kejadian') ?>">
        </label>

        <label>
            Tanggal kejadian
            <input type="date" name="waktu_kejadian" required
                value="<?= $val('waktu_kejadian') ?>">
        </label>

        <label>
            Foto barang (format JPG atau PNG, maks 2 MB)
            <?php if ($isEdit && !empty($data['foto'])): ?>
                <div style="margin-bottom: 8px;">
                    <img src="<?= $val('foto') ?>" alt="Foto saat ini" style="max-height: 120px; border-radius: 4px; display: block; margin-top: 4px;">
                    <small>Foto saat ini. Pilih file baru di bawah jika ingin mengganti.</small>
                </div>
                <input type="hidden" name="foto_lama" value="<?= $val('foto') ?>">
            <?php endif; ?>
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png" <?= $isEdit ? '' : 'required' ?>>
        </label>

        <label>
            Deskripsi
            <textarea name="deskripsi" rows="5" required><?= $val('deskripsi') ?></textarea>
        </label>

        <button type="submit"><?= $isEdit ? 'Simpan' : 'Kirim' ?></button>
    </form>
</main>