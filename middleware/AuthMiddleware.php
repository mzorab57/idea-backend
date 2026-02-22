<?php
require_once __DIR__ . '/../utils/JWTHandler.php';
class AuthMiddleware {
    public static function requireRole(array $roles): callable {
        return function () use ($roles) {
            $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/Bearer\s+(.+)/', $hdr, $m)) {
                Response::json(['error' => 'Unauthorized'], 401);
                return false;
            }
            try {
                $payload = JWTHandler::decode($m[1]);
            } catch (Throwable $e) {
                Response::json(['error' => 'Invalid token'], 401);
                return false;
            }
            if (!in_array($payload['role'] ?? '', $roles, true)) {
                Response::json(['error' => 'Forbidden'], 403);
                return false;
            }
            $GLOBALS['auth_user'] = $payload;
            return true;
        };
    }
}
