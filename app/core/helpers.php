<?php

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#i', $path) === 1) {
            return $path;
        }

        return BASE_PATH . '/' . ltrim($path, '/');
    }
}

if (!function_exists('tanggal_id')) {
    function tanggal_id(?string $date, bool $denganWaktu = false): string
    {
        if ($date === null || trim($date) === '') {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $bulan = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        $hasil = date('j', $timestamp)
            . ' ' . $bulan[(int) date('n', $timestamp)]
            . ' ' . date('Y', $timestamp);

        if ($denganWaktu) {
            $hasil .= ' · ' . date('H:i', $timestamp);
        }

        return $hasil;
    }
}

if (!function_exists('inisial')) {
    function inisial(?string $nama): string
    {
        $nama = trim((string) $nama);
        if ($nama === '') {
            return '?';
        }

        $huruf = function_exists('mb_substr')
            ? mb_substr($nama, 0, 1, 'UTF-8')
            : substr($nama, 0, 1);

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($huruf, 'UTF-8')
            : strtoupper($huruf);
    }
}
