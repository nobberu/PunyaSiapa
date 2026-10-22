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
     * simpan user baru dan kembalikan ID jika berhasil
     *
     * @return int|null ID pengguna baru, atau null jika username sudah digunakan
     */
    public function create(array $data): ?int
    {
        $sql = 'INSERT INTO pengguna (nama, username, password, role, nomor_kontak)
            VALUES (:nama, :username, :password, :role, :nomor_kontak)
            ON CONFLICT (username) DO NOTHING
            RETURNING id_pengguna';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nama'         => $data['nama'],
            'username'     => $data['username'],
            'password'     => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'         => $data['role'] ?? 'user',
            'nomor_kontak' => $data['nomor_kontak'],
        ]);

        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }
}
