<?php

/** @var string $mode   'create' | 'edit' */
/** @var array|null $data */
/** @var array $kategori */
/** @var string|null $error */

$isEdit = ($mode === 'edit');
$data   = $data ?? [];
$base   = BASE_PATH . '/ditemukan';
$action = $isEdit ? $base . '/' . (int) ($data['id_postingan'] ?? 0) : $base;
$detail = $base . '/' . (int) ($data['id_postingan'] ?? 0);

$title  = ($isEdit ? 'Edit' : 'Buat') . ' Laporan - PunyaSiapa';
$styles = ['pages/form'];

$val = function (string $key) use ($data): string {
    return htmlspecialchars((string) ($data[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

$statuses = ['aktif' => 'Aktif', 'selesai' => 'Selesai'];
$hasPhoto = $isEdit && !empty($data['foto']);
?>

<main class="form-page">
    <header class="form-page-header">
        <h1 class="page-title"><?= $isEdit ? 'Edit laporan' : 'Buat laporan' ?></h1>
        <p class="text-muted">
            <?= $isEdit
                ? 'Perbarui informasi barang temuan yang kamu laporkan.'
                : 'Bagikan detail barang temuan agar pemiliknya bisa mengenali.' ?>
        </p>
    </header>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <input type="hidden" name="_csrf"
            value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

        <div class="photo-field" data-photo-field>
            <label class="field-label" for="foto">Foto barang</label>

            <label class="photo-field-frame" for="foto">
                <img class="photo-field-preview" data-photo-preview
                    <?php if ($hasPhoto): ?>src="<?= htmlspecialchars(asset((string) $data['foto'])) ?>" <?php endif; ?>
                    alt="Pratinjau foto barang" <?= $hasPhoto ? '' : 'hidden' ?>>

                <span class="photo-field-placeholder" data-photo-placeholder <?= $hasPhoto ? 'hidden' : '' ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                        <path d="M208,56H180.28L166.65,35.56A8,8,0,0,0,160,32H96a8,8,0,0,0-6.65,3.56L75.71,56H48A24,24,0,0,0,24,80V192a24,24,0,0,0,24,24H208a24,24,0,0,0,24-24V80A24,24,0,0,0,208,56Zm8,136a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V80a8,8,0,0,1,8-8H80a8,8,0,0,0,6.66-3.56L100.28,48h55.43l13.63,20.44A8,8,0,0,0,176,72h32a8,8,0,0,1,8,8ZM128,88a44,44,0,1,0,44,44A44.05,44.05,0,0,0,128,88Zm0,72a28,28,0,1,1,28-28A28,28,0,0,1,128,160Z"></path>
                    </svg>
                    <span class="photo-field-title">Pilih foto barang</span>
                    <span class="field-hint">JPG atau PNG, maksimal 2 MB</span>
                </span>
            </label>

            <input class="file-input" type="file" id="foto" name="foto"
                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                data-photo-input <?= $isEdit ? '' : 'required' ?>>

            <p class="field-hint">
                <?= $isEdit
                    ? 'Pilih berkas baru untuk mengganti foto saat ini.'
                    : 'Tambahkan foto agar pemiliknya mudah mengenali.' ?>
            </p>
        </div>

        <div class="form-grid">
            <div class="field field-full">
                <label class="field-label" for="nama_barang">Nama barang</label>
                <input type="text" id="nama_barang" name="nama_barang" maxlength="100" required
                    placeholder="Powerbank 10000mAh" value="<?= $val('nama_barang') ?>">
            </div>

            <div class="field">
                <label class="field-label" for="id_kategori">Kategori</label>
                <select id="id_kategori" name="id_kategori" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= (int) $k['id_kategori'] ?>"
                            <?= ((int) ($data['id_kategori'] ?? 0) === (int) $k['id_kategori']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label class="field-label" for="status_barang">Status barang</label>
                <select id="status_barang" name="status_barang" required>
                    <?php foreach ($statuses as $value => $text): ?>
                        <option value="<?= $value ?>"
                            <?= (($data['status_barang'] ?? '') === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($text) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label class="field-label" for="lokasi_kejadian">Lokasi penemuan</label>
                <input type="text" id="lokasi_kejadian" name="lokasi_kejadian" maxlength="100" required
                    placeholder="Lab Komputer 2" value="<?= $val('lokasi_kejadian') ?>">
            </div>

            <div class="field">
                <label class="field-label" for="waktu_kejadian">Tanggal penemuan</label>
                <input type="date" id="waktu_kejadian" name="waktu_kejadian" required
                    value="<?= $val('waktu_kejadian') ?>">
            </div>

            <div class="field field-full">
                <label class="field-label" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="5" required
                    placeholder="Jelaskan ciri-ciri barang dan di mana barang ditemukan."><?= $val('deskripsi') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-outlined" href="<?= $isEdit ? $detail : $base ?>">Batal</a>
            <button class="btn" type="submit"><?= $isEdit ? 'Simpan perubahan' : 'Kirim laporan' ?></button>
        </div>
    </form>
</main>