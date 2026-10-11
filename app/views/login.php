<?php
/** @var string|null $error */
$error = $error ?? null;

$title = 'Masuk - PunyaSIapa';

ob_start();
?>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/layout.php';
