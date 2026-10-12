<?php
$error = $error ?? null;
$csrfToken = $csrfToken ?? '';

$title = 'Masuk - PunyaSiapa';
?>
<main class="auth">
    <h1>Masuk</h1>

    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_PATH ?>/login">
        <input
            type="hidden"
            name="_csrf"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label>
            Username
            <input type="text" name="username" required autofocus>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Masuk</button>
    </form>

    <p>Belum punya akun? <a href="<?= BASE_PATH ?>/register">Daftar</a></p>
</main>