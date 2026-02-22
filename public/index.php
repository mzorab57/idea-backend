<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../middleware/SecurityHeadersMiddleware.php';
if (!isset($_SERVER['HTTP_AUTHORIZATION']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
SecurityHeadersMiddleware::handle();
if (CorsMiddleware::handle() === false) {
    return;
}
$router = new Router();
require_once __DIR__ . '/../routes/api.php';
try {
    $router->dispatch();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    echo json_encode(['error' => $debug ? ($e->getMessage()) : 'Server error']);
}
