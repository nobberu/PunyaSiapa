<?php

require_once __DIR__ . '/../config/config.php';

/**
 * Koneksi database PostgreSQL.
 *
 * Singleton sederhana: panggil Database::connect() untuk mendapatkan objek PDO
 * yang dipakai bersama. Kredensial diambil dari app/config/config.php.
 */
class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    /**
     * Buka (atau kembalikan) koneksi PDO ke PostgreSQL.
     *
     * @return PDO
     */
    public static function connect()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Variabel dari config.php tersedia di scope global.
        global $db_host, $db_port, $db_user, $db_pass, $db_name;

        $host = $db_host ?? 'localhost';
        $port = $db_port ?? '5432';
        $name = $db_name ?? 'punyasiapa';
        $user = $db_user ?? 'postgres';
        $pass = $db_pass ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // error dilempar
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // hasil array asosiatif
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statement asli
            ]);
        } catch (PDOException $e) {
            die('Koneksi database gagal: ' . $e->getMessage());
        }

        return self::$pdo;
    }
}
