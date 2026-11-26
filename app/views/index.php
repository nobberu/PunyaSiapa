<?php
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}

header('Location: ' . $basePath . '/ditemukan', true, 302);
exit;