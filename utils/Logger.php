<?php
require_once __DIR__ . '/../config/database.php';
class Logger {
    public static function adminLog(int $userId, string $actionType, ?string $description = null, ?string $table = null, ?int $recordId = null): void {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO admin_logs (user_id, action_type, description, table_name, record_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt->execute([$userId, $actionType, $description, $table, $recordId, $ip]);
    }
}
