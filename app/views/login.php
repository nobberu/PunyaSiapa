<?php
$error = $error ?? null;
$csrfToken = $csrfToken ?? "";
$title = "Masuk - PunyaSiapa";
$styles = ['pages/auth'];
?>
<main class="auth">
    <h1 class="page-title">Masuk</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_PATH ?>/login">
        <input
            type="hidden"
            name="_csrf"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">

        <div class="field">
            <label class="field-label" for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label class="field-label" for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <button class="btn btn-block" type="submit">Masuk</button>
    </form>

    <p class="auth-switch">Belum punya akun? <a href="<?= BASE_PATH ?>/register">Daftar</a></p>
</main>
