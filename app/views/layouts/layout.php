<?php
/** @var string $content */
/** @var string|null $title */
$title = $title ?? 'PunyaSIapa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/global.css">
</head>
<body>
    <?= $content ?>

    <script src="<?= BASE_PATH ?>/js/global.js"></script>
</body>
</html>
