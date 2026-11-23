<?php

require_once __DIR__ . '/../../models/Report.php';
?>
<dialog class="dialog" id="report-dialog" aria-labelledby="report-dialog-title">
    <form id="form-report" class="dialog-form" method="post" action="">
        <input type="hidden" name="_csrf"
            value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

        <h2 class="dialog-title" id="report-dialog-title">Laporkan Konten</h2>
        <p class="dialog-text" id="report-dialog-target">Pilih alasan laporan yang sesuai.</p>

        <div class="field">
            <label class="field-label" for="report-kategori">Kategori laporan</label>
            <select id="report-kategori" name="kategori_report" required>
                <option value="">-- Pilih kategori --</option>
                <?php foreach (Report::KATEGORI as $kode => $label): ?>
                    <option value="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field-label" for="report-alasan">
                Keterangan tambahan
                <span class="field-hint" id="report-alasan-req">(Opsional)</span>
            </label>
            <textarea id="report-alasan" name="alasan_report" rows="3"
                maxlength="<?= Report::MAX_ALASAN_LEN ?>"
                placeholder="Jelaskan singkat mengapa konten ini melanggar ketentuan..."></textarea>
            <span class="field-hint">Maksimal <?= Report::MAX_ALASAN_LEN ?> karakter. Wajib diisi jika memilih kategori Lainnya.</span>
        </div>

        <div class="dialog-actions">
            <button class="btn btn-outlined btn-sm" type="button" id="btn-batal-report">Batal</button>
            <button class="btn btn-danger btn-sm" type="submit" id="btn-kirim-report">Kirim Laporan</button>
        </div>
    </form>
</dialog>