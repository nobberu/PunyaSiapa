<?php

use punyaSiapa\Http\Request;

/**
 * Router inti PunyaSIapa.
 *
 * Router sederhana berbasis tabel rute + regex, tanpa dependency maupun
 * autoloader. Mendukung placeholder dinamis:
 *   /barang/{id}        -> cocokkan segment apa pun
 *   /barang/{id:\d+}    -> cocokkan dengan pola regex kustom
 */
class Router
{
    /** @var array<int, array{method:string, regex:string, handler:mixed}> */
    private $routes = [];

    public function get(string $path, $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, $handler): void    { $this->add('PUT', $path, $handler); }
    public function delete(string $path, $handler): void { $this->add('DELETE', $path, $handler); }

    /**
     * Daftarkan satu rute.
     *
     * @param string $method  GET|POST|PUT|DELETE
     * @param string $path    pola path, mis. "/barang/{id:\d+}"
     * @param mixed  $handler closure, atau string "Controller@method"
     */
    public function add(string $method, string $path, $handler): void
    {
        // Ubah "/barang/{id}" atau "/barang/{id:\d+}" menjadi named-group regex
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            function ($m) {
                $inner = (isset($m[2]) && $m[2] !== '') ? $m[2] : '[^/]+';
                return '(?P<' . $m[1] . '>' . $inner . ')';
            },
            $path
        );

        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => '#^' . rtrim($pattern, '/') . '/?$#',
            'handler' => $handler,
        ];
    }

    /**
     * Cocokkan request dengan tabel rute.
     *
     * @return array{0:string, 1:mixed, 2:array}
     *   status = 'found' | 'method_not_allowed' | 'not_found'
     */
    public function dispatch(Request $request): array
    {
        $method  = $request->method();
        $uri     = $request->uri();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                if ($route['method'] === $method) {
                    // Ambil hanya named captures (parameter)
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    return ['found', $route['handler'], $params];
                }
                $allowed[] = $route['method'];
            }
        }

        if ($allowed) {
            return ['method_not_allowed', array_values(array_unique($allowed)), []];
        }

        return ['not_found', null, []];
    }

    /**
     * Jalankan rute yang cocok dan kirim respons.
     */
    public function run(Request $request): void
    {
        list($status, $handler, $params) = $this->dispatch($request);

        switch ($status) {
            case 'found':
                $this->invoke($handler, $params);
                break;

            case 'method_not_allowed':
                http_response_code(405);
                header('Allow: ' . implode(', ', $handler));
                echo '405 Method Not Allowed';
                break;

            default:
                http_response_code(404);
                echo '404 Not Found';
        }
    }

    /**
     * Panggil handler: closure/callable atau "Controller@method".
     */
    private function invoke($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            call_user_func_array([new $class(), $method], $params);
            return;
        }

        throw new RuntimeException('Invalid route handler.');
    }
}
