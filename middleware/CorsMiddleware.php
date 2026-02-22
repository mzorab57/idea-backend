<?php
class CorsMiddleware {
    public static function handle(): bool {
        $cfg = require __DIR__ . '/../config/cors.php';
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $origins = array_filter(array_map('trim', explode(',', (string)$cfg['origin'])));
        $allowOrigin = null;
        if (in_array('*', $origins, true)) {
            $allowOrigin = '*';
        } elseif ($requestOrigin && in_array($requestOrigin, $origins, true)) {
            $allowOrigin = $requestOrigin;
        }
        if ($allowOrigin !== null) {
            header('Access-Control-Allow-Origin: ' . $allowOrigin);
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Methods: ' . $cfg['methods']);
        header('Access-Control-Allow-Headers: ' . $cfg['headers']);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            return false;
        }
        return true;
    }
}
