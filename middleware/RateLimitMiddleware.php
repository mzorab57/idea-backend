<?php
require_once __DIR__ . '/../config/database.php';
class RateLimitMiddleware {
    public static function downloadsPerMinute(int $limit = 5, int $windowSeconds = 60): callable {
        return function () use ($limit, $windowSeconds) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $db = Database::connection();
            $windowSeconds = (int)$windowSeconds;
            $sql = "SELECT COUNT(*) AS cnt FROM logs WHERE ip_address = ? AND action_type = 'download' AND created_at >= (NOW() - INTERVAL {$windowSeconds} SECOND)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$ip]);
            $row = $stmt->fetch();
            if ((int)$row['cnt'] > $limit) {
                Response::json(['error' => 'Rate limit exceeded'], 429);
                return false;
            }
            return true;
        };
    }
    public static function perIpActionLimit(string $action, int $limit, int $windowSeconds): callable {
        return function () use ($action, $limit, $windowSeconds) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $db = Database::connection();
            $windowSeconds = (int)$windowSeconds;
            $action = trim($action);
            $sql = "SELECT COUNT(*) AS cnt FROM logs WHERE ip_address = ? AND action_type = ? AND created_at >= (NOW() - INTERVAL {$windowSeconds} SECOND)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$ip, $action]);
            $row = $stmt->fetch();
            if ((int)$row['cnt'] >= $limit) {
                Response::json(['error' => 'Too many attempts'], 429);
                return false;
            }
            return true;
        };
    }
}
