<?php

/** @var string $content */
/** @var string|null $title */
$title = $title ?? "PunyaSiapa";

$user = Auth::check() ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/global.css">
</head>

<body>
    <header class="site-header">
        <a class="brand" href="<?= BASE_PATH ?>/">PunyaSiapa</a>

        <nav class="site-nav">
            <?php if ($user !== null): ?>
                <span class="user-name">Hello, <?= htmlspecialchars($user["nama"],) ?></span>
                <form method="post" action="<?= BASE_PATH ?>/logout">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a href="<?= BASE_PATH ?>/login">Masuk</a>
                <a href="<?= BASE_PATH ?>/register">Daftar</a>
            <?php endif; ?>
        </nav>
    </header>

    <?= $content ?>

    <script src="<?= BASE_PATH ?>/js/global.js"></script>
</body>

</html>