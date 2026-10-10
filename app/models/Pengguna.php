<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Model untuk tabel pengguna.
 */
class Pengguna
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    /** Cari satu user berdasarkan username. */
    public function findByUsername(string $username)
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pengguna WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Cari satu user berdasarkan id. */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pengguna WHERE id_pengguna = :id'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Simpan user baru (password di-hash oleh pengguna).
     *
     * @return int id_pengguna yang baru dibuat
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO pengguna (nama, username, password, role, nomor_kontak)
                VALUES (:nama, :username, :password, :role, :nomor_kontak)
                RETURNING id_pengguna';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nama'         => $data['nama'],
            'username'     => $data['username'],
            'password'     => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'         => $data['role'] ?? 'user',
            'nomor_kontak' => $data['nomor_kontak'],
        ]);

        return (int) $stmt->fetchColumn();
    }
}