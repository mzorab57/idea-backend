<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
class AuthorManager extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function all(): array {
                $stmt = $this->db->query("SELECT id, name, slug, bio, image, is_active, created_at FROM authors ORDER BY id DESC");
                return $stmt->fetchAll();
            }
        };
        \Response::json($m->all());
    }
    public function create(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function create(array $d): int {
                $stmt = $this->db->prepare("INSERT INTO authors (name, slug, bio, image, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$d['name'] ?? '', $d['slug'] ?? '', $d['bio'] ?? null, $d['image'] ?? null, isset($d['is_active']) ? (int)$d['is_active'] : 1]);
                return (int)$this->db->lastInsertId();
            }
        };
        $id = $m->create($d);
        \Logger::adminLog((int)$u['sub'], 'create', $d['name'] ?? '', 'authors', $id);
        \Response::json(['id' => $id], 201);
    }
    public function update(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function update(int $id, array $d): void {
                $this->db->prepare("UPDATE authors SET name = ?, slug = ?, bio = ?, image = ?, is_active = ? WHERE id = ?")->execute([
                    $d['name'] ?? '', $d['slug'] ?? '', $d['bio'] ?? null, $d['image'] ?? null, isset($d['is_active']) ? (int)$d['is_active'] : 1, $id
                ]);
            }
        };
        $m->update((int)$id, $d);
        \Logger::adminLog((int)$u['sub'], 'update', $d['name'] ?? '', 'authors', (int)$id);
        \Response::json(['id' => (int)$id]);
    }
    public function delete(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $m = new class extends \Model {
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM authors WHERE id = ?")->execute([$id]);
            }
        };
        $m->delete((int)$id);
        \Logger::adminLog((int)$u['sub'], 'delete', null, 'authors', (int)$id);
        \Response::json(['deleted' => true]);
    }
}
