<?php
require_once __DIR__ . '/../config/database.php';
class Permission {
    public static function can(int $userId, string $resource, string $action): bool {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT can_create, can_update, can_delete FROM employee_permissions WHERE user_id = ? AND resource = ? LIMIT 1");
            $stmt->execute([$userId, $resource]);
            $row = $stmt->fetch();
            if (!$row) return false;
            if ($action === 'create') return (int)$row['can_create'] === 1;
            if ($action === 'update') return (int)$row['can_update'] === 1;
            if ($action === 'delete') return (int)$row['can_delete'] === 1;
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
