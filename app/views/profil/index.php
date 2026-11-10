<?php

$title       = 'Profil - PunyaSiapa';
$styles      = ['components/postingan', 'pages/profile'];
$peran       = ($pengguna['role'] ?? '') === 'admin' ? 'Admin' : 'Pengguna';

$kehilanganBase = BASE_PATH . '/kehilangan';
$ditemukanBase  = BASE_PATH . '/ditemukan';

$showAuthor = false;
$fotoProfil = $pengguna['foto_profil'] ?? null;
?>

<main class="profile">
    <!-- Hero Profile Section -->
    <section class="card profile-hero" aria-labelledby="profil-nama">
        <!-- PFP / Initial Container -->
        <div class="profile-hero-avatar" id="heroPfpContainer">
            <?php if (!empty($fotoProfil)): ?>
                <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="Foto Profil" class="profile-avatar-img">
            <?php else: ?>
                <span class="profile-avatar-initial"><?= htmlspecialchars(inisial($pengguna['nama'])) ?></span>
            <?php endif; ?>
        </div>

        <div class="profile-hero-identity">
            <span class="chip"><?= htmlspecialchars($peran) ?></span>
            <h1 id="profil-nama"><?= htmlspecialchars($pengguna['nama']) ?></h1>
            <p class="profile-hero-meta">
                <span>@<?= htmlspecialchars($pengguna['username']) ?></span>
                <span aria-hidden="true">&middot;</span>
                <span><?= htmlspecialchars($pengguna['nomor_kontak'] ?? '-') ?></span>
            </p>
        </div>

        <div class="profile-hero-actions">
            <!-- Tombol Trigger Modal Edit -->
            <button type="button" class="btn btn-outlined" onclick="openEditModal()">Edit Profil</button>
            <a class="btn btn-tonal" href="<?= $kehilanganBase ?>/create">Lapor kehilangan</a>
            <a class="btn btn-outlined" href="<?= $ditemukanBase ?>/create">Lapor temuan</a>
        </div>
    </section>

    <!-- Modal Edit Profil -->
    <dialog id="editProfileModal" class="profile-modal">
        <div class="card modal-content">
            <header class="modal-header">
                <h2>Edit Profil</h2>
                <button type="button" class="btn-close" onclick="closeEditModal()" aria-label="Tutup">&times;</button>
            </header>

            <form action="<?= BASE_PATH ?>/profil/update" method="POST" enctype="multipart/form-data" class="edit-profile-form">
                <div class="avatar-upload-group">
                    <div class="avatar-preview" onclick="document.getElementById('pfpInput').click()">
                        <?php if (!empty($fotoProfil)): ?>
                            <img id="modalPfpImg" src="<?= htmlspecialchars($fotoProfil) ?>" alt="Preview PFP" class="profile-avatar-img">
                            <span id="modalPfpInitial" class="profile-avatar-initial hidden"><?= htmlspecialchars(inisial($pengguna['nama'])) ?></span>
                        <?php else: ?>
                            <img id="modalPfpImg" src="" alt="Preview PFP" class="profile-avatar-img hidden">
                            <span id="modalPfpInitial" class="profile-avatar-initial"><?= htmlspecialchars(inisial($pengguna['nama'])) ?></span>
                        <?php endif; ?>
                        <div class="avatar-overlay">Ganti Foto</div>
                    </div>
                    <input type="file" name="foto_profil" id="pfpInput" class="hidden-input" accept="image/*" onchange="previewAvatar(event)">
                </div>

                <div class="form-group">
                    <label for="inputNama">Nama Lengkap</label>
                    <input type="text" id="inputNama" name="nama" value="<?= htmlspecialchars($pengguna['nama']) ?>" required oninput="syncModalInitial(this.value)">
                </div>

                <div class="form-group">
                    <label for="inputKontak">Nomor WhatsApp / Kontak</label>
                    <input type="text" id="inputKontak" name="nomor_kontak" value="<?= htmlspecialchars($pengguna['nomor_kontak'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                </div>

                <footer class="modal-actions">
                    <button type="button" class="btn btn-outlined" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-tonal">Simpan Perubahan</button>
                </footer>
            </form>
        </div>
    </dialog>

    <!-- Activity Stats -->
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

    <!-- Laporan Kehilangan -->
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

    <!-- Laporan Temuan -->
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

<script>
function openEditModal() {
    const modal = document.getElementById('editProfileModal');
    if (typeof modal.showModal === 'function') {
        modal.showModal();
    } else {
        modal.setAttribute('open', 'true');
    }
}

function closeEditModal() {
    const modal = document.getElementById('editProfileModal');
    if (typeof modal.close === 'function') {
        modal.close();
    } else {
        modal.removeAttribute('open');
    }
}

function getInitials(name) {
    const trimmed = name.trim();
    if (!trimmed) return '??';
    const parts = trimmed.split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
}

function syncModalInitial(name) {
    const imgEl = document.getElementById('modalPfpImg');
    const initialEl = document.getElementById('modalPfpInitial');
    
    if (imgEl.classList.contains('hidden') || !imgEl.src) {
        initialEl.textContent = getInitials(name);
    }
}

function previewAvatar(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const imgEl = document.getElementById('modalPfpImg');
        const initialEl = document.getElementById('modalPfpInitial');

        imgEl.src = e.target.result;
        imgEl.classList.remove('hidden');
        initialEl.classList.add('hidden');
    };
    reader.readAsDataURL(file);
}
</script>