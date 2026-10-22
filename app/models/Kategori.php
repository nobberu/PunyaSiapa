<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk tabel kategori.
 */
class Kategori
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /** Semua kategori, terurut berdasarkan nama. */
    public function all(): array
    {
        return $this->pdo
            ->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')
            ->fetchAll();
    }

    /** Cek apakah kategori dengan id tertentu ada. */
    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM kategori WHERE id_kategori = :id');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }
}
