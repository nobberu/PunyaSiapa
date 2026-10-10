<?php
/** @var string|null $error */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - PunyaSIapa</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/global.css">
</head>
<body>
    <main class="auth">
        <h1>Daftar</h1>

        <?php if ($error !== null): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= BASE_PATH ?>/register">
            <label>
                Nama
                <input type="text" name="nama" required autofocus>
            </label>
            <label>
                Username
                <input type="text" name="username" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required minlength="6">
            </label>
            <label>
                Nomor Kontak
                <input type="text" name="nomor_kontak" required>
            </label>
            <button type="submit">Daftar</button>
        </form>

        <p>Sudah punya akun? <a href="<?= BASE_PATH ?>/login">Masuk</a></p>
    </main>

    <script src="<?= BASE_PATH ?>/js/global.js"></script>
</body>
</html>
