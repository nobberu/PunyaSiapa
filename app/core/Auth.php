<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../models/Pengguna.php';

/**
 * Bantuan autentikasi: session, login/logout, dan guard route.
 */
class Auth
{
    /** Mulai session dengan opsi cookie yang aman. */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                // 'secure' => true, // aktifkan saat sudah HTTPS
            ]);
            session_start();
        }
    }

    /** Validasi kredensial; jika benar langsung mulai login. */
    public static function attempt(string $username, string $password): bool
    {
        $user = (new Pengguna())->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            self::login($user);
            return true;
        }
        return false;
    }

    /** Simpan identitas user ke session. */
    public static function login(array $user): void
    {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['user'] = [
            'id'   => (int) $user['id_pengguna'],
            'nama' => $user['nama'],
            'role' => $user['role'],
        ];
    }

    /** @return bool true jika ada user yang login */
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /** @return array|null data user yang login */
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role()
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /** Hapus session dan cookie-nya. */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Redirect ke halaman login bila belum login. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_PATH . '/auth/login');
            exit;
        }
    }

    /** Hanya boleh diakses admin. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }
}