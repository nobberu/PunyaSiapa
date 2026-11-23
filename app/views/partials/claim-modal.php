<?php

/**
 * Kontrak Partial View (Modal Pengajuan Klaim — WBS 3.6.1)
 * @var int $id ID postingan ditemukan
 */

require_once __DIR__ . '/../../models/Claim.php';
?>
<dialog class="dialog" id="claim-dialog" aria-labelledby="claim-dialog-title">
    <form id="form-claim" class="dialog-form" method="post"
        action="<?= BASE_PATH ?>/ditemukan/<?= (int) $id ?>/claim"
        enctype="multipart/form-data">
        <input type="hidden" name="_csrf"
            value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

        <h2 class="dialog-title" id="claim-dialog-title">Ajukan Klaim Kepemilikan</h2>
        <p class="dialog-text">
            Bukti ini <strong>bersifat rahasia</strong> — hanya dapat dilihat oleh penemu barang dan Admin untuk verifikasi.
        </p>

        <div class="field">
            <label class="field-label" for="claim-bukti">Ciri khusus / bukti kepemilikan</label>
            <textarea id="claim-bukti" name="bukti_claim" rows="4" required
                minlength="<?= Claim::MIN_BUKTI_LEN ?>"
                maxlength="<?= Claim::MAX_BUKTI_LEN ?>"
                placeholder="Sebutkan ciri rahasia barang (misal: wallpaper, stiker, isi di dalamnya, goresan khusus)..."></textarea>
            <span class="field-hint">
                Minimal <?= Claim::MIN_BUKTI_LEN ?> karakter, maksimal <?= Claim::MAX_BUKTI_LEN ?> karakter.
            </span>
        </div>

        <div class="field">
            <label class="field-label" for="claim-foto">Foto bukti tambahan <span class="field-hint">(Opsional)</span></label>
            <input id="claim-foto" type="file" name="foto_bukti" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
            <span class="field-hint">Format JPG atau PNG, maksimal 2 MB (misal foto nota / foto barang sebelumnya).</span>
        </div>

        <div class="dialog-actions">
            <button class="btn btn-outlined btn-sm" type="button" id="btn-batal-claim">Batal</button>
            <button class="btn btn-sm" type="submit" id="btn-kirim-claim">Kirim Klaim</button>
        </div>
    </form>
</dialog>