<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Pengguna.php';
require_once __DIR__ . '/../../src/http/request.php';

use punyaSiapa\Http\Request;

/**
 * Autentikasi: register, login, dan logout.
 */
class AuthController
{
    public function showLogin()
    {
        echo View::render('login', [
            'csrfToken' => Auth::csrfToken(),
        ]);
    }

    public function showRegister()
    {
        echo View::render('register', [
            'csrfToken' => Auth::csrfToken(),
        ]);
    }

    public function login()
    {
        $r        = new Request();
        // reject jika token formulir nya tidak cocok sama session
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);

            echo View::render('login', [
                'error'     => 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.',
                'csrfToken' => Auth::csrfToken(),
            ]);

            return;
        }
        $username = trim((string) $r->post('username'));
        $password = (string) $r->post('password');

        if (Auth::attempt($username, $password)) {
            header('Location: ' . BASE_PATH . '/');
            exit;
        }

        $error = 'Username atau password salah.';

        echo View::render('login', [
            'error'     => $error,
            'csrfToken' => Auth::csrfToken(),
        ]);
    }

    public function register()
    {
        $r           = new Request();
        // reject pendaftaran jika token formulirnya tidak cocok dengan session
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);

            echo View::render('register', [
                'error'     => 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.',
                'csrfToken' => Auth::csrfToken(),
            ]);

            return;
        }
        $nama        = trim((string) $r->post('nama'));
        $username    = trim((string) $r->post('username'));
        $password    = (string) $r->post('password');
        $nomorKontak = trim((string) $r->post('nomor_kontak'));

        // Hitung panjang password
        $passwordLength = function_exists('mb_strlen')
            ? mb_strlen($password, 'UTF-8')
            : strlen($password);

        $error = null;

        if (
            $nama === '' ||
            $username === '' ||
            $password === '' ||
            $nomorKontak === ''
        ) {
            $error = 'Semua kolom wajib diisi.';
        } elseif ($passwordLength < 15) {
            $error = 'Password minimal 15 karakter.';
        } elseif (strlen($password) > 72) {
            $error = 'Password terlalu panjang; maksimal 72 byte.';
        }

        if ($error !== null) {
            echo View::render('register', [
                'error'     => $error,
                'csrfToken' => Auth::csrfToken(),
            ]);

            return;
        }

        $idPengguna = (new Pengguna())->create([
            'nama'         => $nama,
            'username'     => $username,
            'password'     => $password,
            'nomor_kontak' => $nomorKontak,
        ]);

        if ($idPengguna === null) {
            echo View::render('register', [
                'error'     => 'Username sudah dipakai.',
                'csrfToken' => Auth::csrfToken(),
            ]);

            return;
        }

        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    public function logout()
    {
        $r = new Request();
        if (!Auth::verifyCsrf($r->post('_csrf'))) {
            http_response_code(403);
            echo 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.';
            return;
        }

        Auth::logout();

        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
}
