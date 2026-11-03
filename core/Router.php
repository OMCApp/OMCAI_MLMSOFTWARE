<?php
/**
 * Front Controller — ทุก request เข้าที่ public/index.php แล้วมาจับคู่ที่นี่
 *
 * ‼️ ประกาศ route เป็นเส้นทางภายในแอปเสมอ (/login) — Router ตัด base path ให้เองก่อนเทียบ
 *    การเช็คสิทธิ์ต้องผูกที่ middleware ของ route หรือกลุ่ม route เท่านั้น
 *    ห้ามเช็คกระจายในแต่ละ Controller เพราะจะมีจุดที่ลืมเช็คหลุดเสมอ
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,regex:string,params:array<int,string>,action:mixed,middleware:array<int,string>}> */
    private array $routes = [];

    /** @var array<int, string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    /** @var array<string, class-string> */
    private array $middlewareMap = [];

    /** @param array<string, class-string> $map ชื่อสั้น => คลาส */
    public function registerMiddleware(array $map): void
    {
        $this->middlewareMap = $map + $this->middlewareMap;
    }

    public function get(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    public function put(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $action, $middleware);
    }

    public function patch(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->add('PATCH', $pattern, $action, $middleware);
    }

    public function delete(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $action, $middleware);
    }

    /**
     * @param array{prefix?:string,middleware?:array<int,string>|string} $options
     */
    public function group(array $options, callable $define): void
    {
        $prefix     = $this->groupPrefix;
        $middleware = $this->groupMiddleware;

        $this->groupPrefix .= rtrim((string) ($options['prefix'] ?? ''), '/');

        $extra = $options['middleware'] ?? [];
        $this->groupMiddleware = array_merge($middleware, is_array($extra) ? $extra : [$extra]);

        $define($this);

        $this->groupPrefix     = $prefix;
        $this->groupMiddleware = $middleware;
    }

    /** @param array<int, string> $middleware */
    private function add(string $method, string $pattern, mixed $action, array $middleware): void
    {
        $pattern = $this->groupPrefix . '/' . trim($pattern, '/');
        $pattern = $pattern === '/' ? '/' : rtrim($pattern, '/');
        $pattern = $pattern === '' ? '/' : $pattern;

        $params = [];
        $regex  = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];

                return '(' . ($m[2] ?? '[^/]+') . ')';
            },
            $pattern
        );

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'regex'      => '#^' . $regex . '$#',
            'params'     => $params,
            'action'     => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    /** @return array<int, array<string, mixed>> ใช้โดย check.php และหน้าตรวจภายใน */
    public function all(): array
    {
        return $this->routes;
    }

    public function dispatch(): void
    {
        $path   = Path::current();
        $method = $this->method();

        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], $matches) ?: [];

            $this->runMiddleware($route['middleware']);
            $this->runAction($route['action'], $params);

            return;
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            $this->fail(405);

            return;
        }

        $this->fail(404);
    }

    /** รองรับ method spoofing เพราะฟอร์ม HTML ส่งได้แค่ GET/POST */
    private function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);

            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }

        return $method;
    }

    /** @param array<int, string> $names */
    private function runMiddleware(array $names): void
    {
        foreach ($names as $name) {
            $class = $this->middlewareMap[$name] ?? $name;

            if (!class_exists($class)) {
                throw new RuntimeException("Middleware not found: {$name}");
            }

            (new $class())->handle();
        }
    }

    /** @param array<string, string> $params */
    private function runAction(mixed $action, array $params): void
    {
        if (is_callable($action)) {
            $action($params);

            return;
        }

        if (!is_string($action) || !str_contains($action, '@')) {
            throw new RuntimeException('Route action must be "Controller@method" or a callable');
        }

        [$controller, $method] = explode('@', $action, 2);
        $class = str_starts_with($controller, '\\') ? $controller : 'App\\Controllers\\' . $controller;

        if (!class_exists($class)) {
            throw new RuntimeException("Controller not found: {$class}");
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException("Method not found: {$class}@{$method}");
        }

        $instance->{$method}(...array_values($params));
    }

    private function fail(int $status): void
    {
        http_response_code($status);

        $view = dirname(__DIR__) . '/app/Views/pages/errors/' . $status . '.php';

        if (is_file($view)) {
            (new \App\Controllers\ErrorController())->show($status);

            return;
        }

        echo $status;
    }
}
