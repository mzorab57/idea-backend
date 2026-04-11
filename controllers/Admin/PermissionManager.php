<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
class PermissionManager extends \Controller {
    public function getByUser(string $userId): void {
        $m = new class extends \Model {
            public function listFor(int $uid): array {
                $stmt = $this->db->prepare("SELECT resource, can_create, can_update, can_delete FROM employee_permissions WHERE user_id = ?");
                $stmt->execute([$uid]);
                $rows = $stmt->fetchAll();
                $out = [];
                foreach ($rows as $r) {
                    $out[$r['resource']] = [
                        'create' => (int)$r['can_create'] === 1,
                        'update' => (int)$r['can_update'] === 1,
                        'delete' => (int)$r['can_delete'] === 1,
                    ];
                }
                return $out;
            }
        };
        \Response::json($m->listFor((int)$userId));
    }
    public function updateForUser(string $userId): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') !== 'admin') { \Response::json(['error' => 'Forbidden'], 403); return; }
        $payload = $this->request['body'] ?? [];
        $m = new class extends \Model {
            public function upsert(int $uid, string $resource, array $flags): void {
                $stmt = $this->db->prepare(
                    "INSERT INTO employee_permissions (user_id, resource, can_create, can_update, can_delete)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE can_create=VALUES(can_create), can_update=VALUES(can_update), can_delete=VALUES(can_delete)"
                );
                $stmt->execute([$uid, $resource, (int)($flags['create'] ?? 0), (int)($flags['update'] ?? 0), (int)($flags['delete'] ?? 0)]);
            }
        };
        $uid = (int)$userId;
        foreach (['categories','subcategories','authors','books'] as $res) {
            if (isset($payload[$res]) && is_array($payload[$res])) {
                $m->upsert($uid, $res, $payload[$res]);
            }
        }
        \Logger::adminLog((int)$u['sub'], 'update', 'permissions', 'employee_permissions', $uid);
        \Response::json(['updated' => true]);
    }
}
