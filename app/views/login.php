<?php
/** @var string|null $error */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - PunyaSIapa</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/global.css">
</head>
<body>
    <main class="auth">
        <h1>Masuk</h1>

        <?php if ($error !== null): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= BASE_PATH ?>/login">
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

    <script src="<?= BASE_PATH ?>/js/global.js"></script>
</body>
</html>
