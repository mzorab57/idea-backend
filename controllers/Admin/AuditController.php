<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class AuditController extends \Controller {
    public function list(): void {
        $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));
        $table = $_GET['table'] ?? null;
        $action = $_GET['action'] ?? null;
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $m = new class extends \Model {
            public function list(array $f, int $limit): array {
                $sql = "SELECT al.id, al.user_id, u.full_name AS user_name, al.action_type, al.table_name, al.record_id, al.description, al.ip_address, al.created_at
                        FROM admin_logs al
                        LEFT JOIN users u ON u.id = al.user_id
                        WHERE 1=1";
                $p = [];
                if (!empty($f['table'])) { $sql .= " AND al.table_name = ?"; $p[] = $f['table']; }
                if (!empty($f['action'])) { $sql .= " AND al.action_type = ?"; $p[] = $f['action']; }
                if (!empty($f['user_id'])) { $sql .= " AND al.user_id = ?"; $p[] = (int)$f['user_id']; }
                $sql .= " ORDER BY al.id DESC LIMIT ?";
                $p[] = $limit;
                $st = $this->db->prepare($sql);
                $st->execute($p);
                return $st->fetchAll();
            }
        };
        \Response::json($m->list(['table' => $table, 'action' => $action, 'user_id' => $userId], $limit));
    }
}
