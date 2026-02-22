<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
class UserManager extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function allUsers(): array {
                $stmt = $this->db->query("SELECT id, full_name, email, role, is_active, last_login, created_at FROM users ORDER BY id DESC");
                return $stmt->fetchAll();
            }
        };
        \Response::json($m->allUsers());
    }
    public function create(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function create(array $d): int {
                $stmt = $this->db->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
                $hash = password_hash($d['password'] ?? '', PASSWORD_BCRYPT);
                $role = in_array($d['role'] ?? 'employee', ['admin', 'employee'], true) ? $d['role'] : 'employee';
                $stmt->execute([$d['full_name'] ?? '', $d['email'] ?? '', $hash, $role, isset($d['is_active']) ? (int)$d['is_active'] : 1]);
                return (int)$this->db->lastInsertId();
            }
        };
        $id = $m->create($d);
        \Logger::adminLog((int)$u['sub'], 'create', $d['email'] ?? '', 'users', $id);
        \Response::json(['id' => $id], 201);
    }
    public function update(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function update(int $id, array $d): void {
                $fields = [];
                $values = [];
                if (isset($d['full_name'])) { $fields[] = "full_name = ?"; $values[] = $d['full_name']; }
                if (isset($d['email'])) { $fields[] = "email = ?"; $values[] = $d['email']; }
                if (isset($d['password']) && $d['password'] !== '') { $fields[] = "password = ?"; $values[] = password_hash($d['password'], PASSWORD_BCRYPT); }
                if (isset($d['is_active'])) { $fields[] = "is_active = ?"; $values[] = (int)$d['is_active']; }
                if (isset($d['role'])) { $fields[] = "role = ?"; $values[] = in_array($d['role'], ['admin', 'employee'], true) ? $d['role'] : 'employee'; }
                if (!$fields) return;
                $values[] = $id;
                $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
                $this->db->prepare($sql)->execute($values);
            }
        };
        $m->update((int)$id, $d);
        \Logger::adminLog((int)$u['sub'], 'update', $d['email'] ?? '', 'users', (int)$id);
        \Response::json(['id' => (int)$id]);
    }
    public function delete(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $m = new class extends \Model {
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'")->execute([$id]);
            }
        };
        $m->delete((int)$id);
        \Logger::adminLog((int)$u['sub'], 'delete', null, 'users', (int)$id);
        \Response::json(['deleted' => true]);
    }
}
