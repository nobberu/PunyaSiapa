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
        require __DIR__ . '/../views/login.php';
    }

    public function showRegister()
    {
        require __DIR__ . '/../views/register.php';
    }

    public function login()
    {
        $r        = new Request();
        $username = trim((string) $r->post('username'));
        $password = (string) $r->post('password');

        if (Auth::attempt($username, $password)) {
            header('Location: ' . BASE_PATH . '/');
            exit;
        }

        $error = 'Username atau password salah.';
        require __DIR__ . '/../views/login.php';
    }

    public function register()
    {
        $r           = new Request();
        $nama        = trim((string) $r->post('nama'));
        $username    = trim((string) $r->post('username'));
        $password    = (string) $r->post('password');
        $nomorKontak = trim((string) $r->post('nomor_kontak'));

        // Validasi sederhana
        $error = null;
        if ($nama === '' || $username === '' || $password === '' || $nomorKontak === '') {
            $error = 'Semua kolom wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ((new Pengguna())->findByUsername($username)) {
            $error = 'Username sudah dipakai.';
        }

        if ($error !== null) {
            require __DIR__ . '/../views/register.php';
            return;
        }

        (new Pengguna())->create([
            'nama'         => $nama,
            'username'     => $username,
            'password'     => $password,
            'nomor_kontak' => $nomorKontak,
        ]);

        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    public function logout()
    {
        Auth::logout();
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
}