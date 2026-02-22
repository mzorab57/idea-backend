<?php
require_once __DIR__ . '/../config/jwt.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class JWTHandler {
    private static array $config;
    private static function cfg(): array {
        if (!isset(self::$config)) self::$config = require __DIR__ . '/../config/jwt.php';
        return self::$config;
    }
    public static function encode(array $payload): string {
        $cfg = self::cfg();
        $payload['exp'] = time() + $cfg['expires_in'];
        return JWT::encode($payload, $cfg['secret'], $cfg['alg']);
    }
    public static function decode(string $token): array {
        $cfg = self::cfg();
        return (array)JWT::decode($token, new Key($cfg['secret'], $cfg['alg']));
    }
}
