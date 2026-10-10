<?php

namespace punyaSiapa\Http;

/**
 * Membungkus data request HTTP.
 *
 * Dapat di-inject dengan array agar mudah diuji; jika tidak, memakai
 * superglobal $_GET/$_POST/$_SERVER/$_FILES.
 */
class Request
{
    private $get;
    private $post;
    private $server;
    private $files;

    public function __construct(
        array $get = null,
        array $post = null,
        array $server = null,
        array $files = null
    ) {
        $this->get    = $get    ?? $_GET;
        $this->post   = $post   ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->files  = $files  ?? $_FILES;
    }

    /** Metode HTTP, huruf besar (GET/POST/PUT/DELETE). */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Path URI tanpa query string dan tanpa base folder.
     * Contoh: "/PunyaSiapa/public/barang/3?x=1" -> "/barang/3"
     */
    public function uri(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if ($uri === false || $uri === null || $uri === '') {
            $uri = '/';
        }

        $base = rtrim(dirname($this->server['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        return $uri === '' ? '/' : $uri;
    }

    public function isPost(): bool { return $this->method() === 'POST'; }
    public function isGet(): bool  { return $this->method() === 'GET'; }

    /** Ambil data query string ($_GET). Tanpa argumen -> seluruh array. */
    public function query(?string $key = null, $default = null)
    {
        return $key === null ? $this->get : ($this->get[$key] ?? $default);
    }

    /** Ambil data body form ($_POST). Tanpa argumen -> seluruh array. */
    public function post(?string $key = null, $default = null)
    {
        return $key === null ? $this->post : ($this->post[$key] ?? $default);
    }

    /** Ambil data server ($_SERVER). Tanpa argumen -> seluruh array. */
    public function server(?string $key = null, $default = null)
    {
        return $key === null ? $this->server : ($this->server[$key] ?? $default);
    }

    /** Ambil dari POST dulu, lalu query string. */
    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /** Ambil header, mis. header('X-Requested-With'). */
    public function header(string $name, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    /** Ambil entri file upload ($_FILES). */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /** True jika request berasal dari AJAX/fetch. */
    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }
}
