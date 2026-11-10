<?php
$error = $error ?? null;
$csrfToken = $csrfToken ?? '';
$title = 'Daftar - PunyaSiapa';
$styles = ['pages/auth'];
?>
<main class="auth">
    <h1 class="page-title">Daftar</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_PATH ?>/register">
        <input
            type="hidden"
            name="_csrf"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="field">
            <label class="field-label" for="nama">Nama</label>
            <input
                type="text"
                id="nama"
                name="nama"
                required
                autofocus
                autocomplete="name">
        </div>

        <div class="field">
            <label class="field-label" for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username">
        </div>

        <div class="field">
            <label class="field-label" for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                maxlength="72"
                autocomplete="new-password">
        </div>

        <div class="field">
            <label class="field-label" for="nomor_kontak">Nomor Kontak</label>
            <input
                type="tel"
                id="nomor_kontak"
                name="nomor_kontak"
                required
                autocomplete="tel">
        </div>

        <button class="btn btn-block" type="submit">Daftar</button>
    </form>

    <p class="auth-switch">Sudah punya akun? <a href="<?= BASE_PATH ?>/login">Masuk</a></p>
</main>
