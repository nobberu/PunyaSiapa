<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var string $content */
/** @var string|null $title */
$title = $title ?? "PunyaSiapa";

$user       = Auth::check() ? Auth::user() : null;
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function isActive(string $path, string $currentUri): bool
{
    foreach ([BASE_PATH . $path, $path] as $base) {
        if ($base === '') {
            continue;
        }
        $base = rtrim($base, '/');
        if ($currentUri === $base || strpos($currentUri, $base . '/') === 0) {
            return true;
        }
    }

    return false;
}

$kehilanganActive = isActive('/kehilangan', $currentUri);
$ditemukanActive  = isActive('/ditemukan', $currentUri);
$profilActive     = isActive('/profil', $currentUri);
$loginActive      = isActive('/login', $currentUri);
$registerActive   = isActive('/register', $currentUri);
$searchActive     = isActive('/search', $currentUri);
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
    <?php foreach (($styles ?? []) as $style): ?>
        <?php if (preg_match('/^[a-z0-9_\-\/]+$/i', $style)): ?>
            <link rel="stylesheet" href="<?= BASE_PATH ?>/css/<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') ?>.css">
        <?php endif; ?>
    <?php endforeach; ?>
</head>

<body>
    <header class="app-bar">
        <div class="app-bar-inner">
            <a class="app-bar-brand" href="<?= BASE_PATH ?>/ditemukan">
                <span class="app-bar-mark" aria-hidden="true">PS</span>
                <span>PunyaSiapa</span>
            </a>

            <div class="app-bar-actions">
                <?php if ($user !== null): ?>
                    <a class="app-bar-greeting" href="<?= BASE_PATH ?>/profil" title="Buka profil">
                        <?= htmlspecialchars($user["nama"]) ?>
                    </a>
                    <form class="inline-form" method="post" action="<?= BASE_PATH ?>/logout">
                        <input
                            type="hidden"
                            name="_csrf"
                            value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn-text" type="submit" aria-label="Keluar dari akun">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                                <path d="M144.49,136.49l-40,40a12,12,0,0,1-17-17L107,140H24a12,12,0,0,1,0-24h83L87.51,96.49a12,12,0,0,1,17-17l40,40A12,12,0,0,1,144.49,136.49ZM200,28H136a12,12,0,0,0,0,24h42V204H136a12,12,0,0,0,0,24h64a12,12,0,0,0,12-12V40A12,12,0,0,0,200,28Z"></path>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-container">
        <?= $content ?>
    </div>

    <!-- the nav -->
    <nav class="site-nav" aria-label="Navigasi utama">
        <a class="nav-item <?= $kehilanganActive ? 'is-active' : '' ?>"
            href="<?= BASE_PATH ?>/kehilangan"
            <?= $kehilanganActive ? 'aria-current="page"' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                <path d="M168,112a56,56,0,1,1-56-56A56,56,0,0,1,168,112Zm61.66,117.66a8,8,0,0,1-11.32,0l-50.06-50.07a88,88,0,1,1,11.32-11.31l50.06,50.06A8,8,0,0,1,229.66,229.66ZM112,184a72,72,0,1,0-72-72A72.08,72.08,0,0,0,112,184Z"></path>
            </svg>
            <span>Kehilangan</span>
        </a>
        <a class="nav-item <?= $ditemukanActive ? 'is-active' : '' ?>"
            href="<?= BASE_PATH ?>/ditemukan"
            <?= $ditemukanActive ? 'aria-current="page"' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                <path d="M223.68,66.15,135.68,18a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM128,32l80.35,44L178.57,92.29l-80.35-44Zm0,88L47.65,76,81.56,57.43l80.35,44Zm88,55.85h0l-80,43.79V133.83l32-17.51V142a8,8,0,0,0,16,0V107.56l32-17.51v85.76Z"></path>
            </svg>
            <span>Ditemukan</span>
        </a>
        <a class="nav-item <?= $searchActive ? 'is-active' : '' ?>"
            href="<?= BASE_PATH ?>/search"
            <?= $searchActive ? 'aria-current="page"' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
            </svg>
            <span>Cari</span>
        </a>

        <?php if ($user !== null): ?>
            <a class="nav-item <?= $profilActive ? 'is-active' : '' ?>"
                href="<?= BASE_PATH ?>/profil"
                <?= $profilActive ? 'aria-current="page"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                    <path d="M230.93,220a8,8,0,0,1-6.93,4H32a8,8,0,0,1-6.92-12c15.23-26.33,38.7-45.21,66.09-54.16a72,72,0,1,1,73.66,0c27.39,8.95,50.86,27.83,66.09,54.16A8,8,0,0,1,230.93,220Z"></path>
                </svg>
                <span>Profil</span>
            </a>
        <?php endif; ?>

        <?php if ($user === null): ?>
            <a class="nav-item <?= $loginActive ? 'is-active' : '' ?>"
                href="<?= BASE_PATH ?>/login"
                <?= $loginActive ? 'aria-current="page"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                    <path d="M144.49,136.49l-40,40a12,12,0,0,1-17-17L107,140H24a12,12,0,0,1,0-24h83L87.51,96.49a12,12,0,0,1,17-17l40,40A12,12,0,0,1,144.49,136.49ZM200,28H136a12,12,0,0,0,0,24h42V204H136a12,12,0,0,0,0,24h64a12,12,0,0,0,12-12V40A12,12,0,0,0,200,28Z"></path>
                </svg>
                <span>Masuk</span>
            </a>
            <a class="nav-item nav-item-filled <?= $registerActive ? 'is-active' : '' ?>"
                href="<?= BASE_PATH ?>/register"
                <?= $registerActive ? 'aria-current="page"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                    <path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136ZM144,157.68a68,68,0,1,0-71.9,0c-20.65,6.76-39.23,19.39-54.17,37.17A8,8,0,0,0,24,208H192a8,8,0,0,0,6.13-13.15C183.18,177.07,164.6,164.44,144,157.68Z"></path>
                </svg>
                <span>Daftar</span>
            </a>
        <?php endif; ?>
    </nav>

    <dialog class="dialog" id="confirm-dialog" aria-labelledby="dialog-title">
        <form method="dialog" class="dialog-form">
            <h2 class="dialog-title" id="dialog-title" data-dialog-title>Konfirmasi</h2>
            <p class="dialog-text" data-dialog-text></p>
            <div class="dialog-actions">
                <button class="btn btn-outlined btn-sm" type="submit" value="cancel">Batal</button>
                <button class="btn btn-danger btn-sm" type="submit" value="confirm" data-dialog-confirm>Ya, lanjutkan</button>
            </div>
        </form>
    </dialog>

    <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="false"></div>

    <script src="<?= BASE_PATH ?>/js/global.js" defer></script>
</body>

</html>