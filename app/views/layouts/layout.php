<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var string $content */
/** @var string|null $title */
$title = $title ?? "PunyaSiapa";

$user = Auth::check() ? Auth::user() : null;
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function isActive(string $path, string $currentUri): bool
{
    return $currentUri === BASE_PATH . $path || $currentUri === $path;
}
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
    <div class="page-container">
        <?= $content ?>
    </div>

    <!-- the nav -->
    <nav class="site-nav">
        <a class="nav-link <?= isActive('/kehilangan', $currentUri) ? 'active' : '' ?>" href="<?= BASE_PATH ?>/kehilangan">
            <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 256 256">
                <path d="M168,112a56,56,0,1,1-56-56A56,56,0,0,1,168,112Zm61.66,117.66a8,8,0,0,1-11.32,0l-50.06-50.07a88,88,0,1,1,11.32-11.31l50.06,50.06A8,8,0,0,1,229.66,229.66ZM112,184a72,72,0,1,0-72-72A72.08,72.08,0,0,0,112,184Z"></path>
            </svg>
            <div>Kehilangan</div>
        </a>
        <a class="nav-link <?= isActive('/ditemukan', $currentUri) ? 'active' : '' ?>" href="<?= BASE_PATH ?>/ditemukan">
            <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 256 256">
                <path d="M223.68,66.15,135.68,18a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM128,32l80.35,44L178.57,92.29l-80.35-44Zm0,88L47.65,76,81.56,57.43l80.35,44Zm88,55.85h0l-80,43.79V133.83l32-17.51V142a8,8,0,0,0,16,0V107.56l32-17.51v85.76Z"></path>
            </svg>
            <div>Ditemukan</div>
        </a>


        <?php if ($user !== null): ?>
            <span class="user-name">Hello, <?= htmlspecialchars($user["nama"]) ?></span>
            <form class="inline-form" method="post" action="<?= BASE_PATH ?>/logout">
                <input
                    type="hidden"
                    name="_csrf"
                    value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn-text" type="submit">Keluar</button>
            </form>
        <?php else: ?>
            <a class="nav-link <?= isActive('/login', $currentUri) ? 'active' : '' ?>" href="<?= BASE_PATH ?>/login">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 256 256">
                    <path d="M144.49,136.49l-40,40a12,12,0,0,1-17-17L107,140H24a12,12,0,0,1,0-24h83L87.51,96.49a12,12,0,0,1,17-17l40,40A12,12,0,0,1,144.49,136.49ZM200,28H136a12,12,0,0,0,0,24h42V204H136a12,12,0,0,0,0,24h64a12,12,0,0,0,12-12V40A12,12,0,0,0,200,28Z"></path>
                </svg>
                <div>Masuk</div>
            </a>
            <a class="nav-btn <?= isActive('/register', $currentUri) ? 'active' : '' ?>" href="<?= BASE_PATH ?>/register">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 256 256">
                    <path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136ZM144,157.68a68,68,0,1,0-71.9,0c-20.65,6.76-39.23,19.39-54.17,37.17A8,8,0,0,0,24,208H192a8,8,0,0,0,6.13-13.15C183.18,177.07,164.6,164.44,144,157.68Z"></path>
                </svg>
                <div>Daftar</div>
            </a>
        <?php endif; ?>
    </nav>

    <script src="<?= BASE_PATH ?>/js/global.js"></script>
</body>

</html>