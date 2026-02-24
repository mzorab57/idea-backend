<?php
class Router {
    private array $routes = [];
    public function add(string $method, string $path, $handler, array $middlewares = []): void {
        $this->routes[strtoupper($method)][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        $uri = rtrim($uri, '/') ?: '/';
        $routesForMethod = $this->routes[strtoupper($method)] ?? [];
        foreach ($routesForMethod as $path => $config) {
            $routePath = rtrim($path, '/') ?: '/';
            $pattern = "@^" . preg_replace('@\{[^/]+\}@', '([^/]+)', $routePath) . "/?$@";
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                foreach ($config['middlewares'] as $mw) {
                    $result = $mw();
                    if ($result === false) return;
                }
                call_user_func_array($config['handler'], $matches);
                return;
            }
        }
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
    }
}
