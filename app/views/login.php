<?php
$error = $error ?? null;
$csrfToken = $csrfToken ?? "";
$title = "Masuk - PunyaSiapa";
?>
<main class="auth">
    <h1>Masuk</h1>

    <?php if ($error !== null): ?>
        <div class="error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_PATH ?>/login">
        <input
            type="hidden"
            name="_csrf"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">

        <div class="inputGroup">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus autocomplete="username">
        </div>

        <div class="inputGroup">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit">Masuk</button>
    </form>

    <p>Belum punya akun? <a href="<?= BASE_PATH ?>/register">Daftar</a></p>
</main>