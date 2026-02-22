<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
class CategoryManager extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function all(): array {
                $cats = $this->db->query("SELECT id, name, slug, description, is_active, created_at FROM categories ORDER BY id DESC")->fetchAll();
                $subs = $this->db->query("SELECT id, category_id, name, slug, is_active FROM subcategories ORDER BY id DESC")->fetchAll();
                return ['categories' => $cats, 'subcategories' => $subs];
            }
        };
        \Response::json($m->all());
    }
    public function createCategory(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function create(array $d): int {
                $stmt = $this->db->prepare("INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$d['name'] ?? '', $d['slug'] ?? '', $d['description'] ?? null, isset($d['is_active']) ? (int)$d['is_active'] : 1]);
                return (int)$this->db->lastInsertId();
            }
        };
        $id = $m->create($d);
        \Logger::adminLog((int)$u['sub'], 'create', $d['name'] ?? '', 'categories', $id);
        \Response::json(['id' => $id], 201);
    }
    public function updateCategory(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function update(int $id, array $d): void {
                $this->db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, is_active = ? WHERE id = ?")->execute([
                    $d['name'] ?? '', $d['slug'] ?? '', $d['description'] ?? null, isset($d['is_active']) ? (int)$d['is_active'] : 1, $id
                ]);
            }
        };
        $m->update((int)$id, $d);
        \Logger::adminLog((int)$u['sub'], 'update', $d['name'] ?? '', 'categories', (int)$id);
        \Response::json(['id' => (int)$id]);
    }
    public function deleteCategory(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $m = new class extends \Model {
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            }
        };
        $m->delete((int)$id);
        \Logger::adminLog((int)$u['sub'], 'delete', null, 'categories', (int)$id);
        \Response::json(['deleted' => true]);
    }
    public function createSubcategory(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function create(array $d): int {
                $stmt = $this->db->prepare("INSERT INTO subcategories (category_id, name, slug, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([(int)($d['category_id'] ?? 0), $d['name'] ?? '', $d['slug'] ?? '', isset($d['is_active']) ? (int)$d['is_active'] : 1]);
                return (int)$this->db->lastInsertId();
            }
        };
        $id = $m->create($d);
        \Logger::adminLog((int)$u['sub'], 'create', $d['name'] ?? '', 'subcategories', $id);
        \Response::json(['id' => $id], 201);
    }
    public function updateSubcategory(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $d = $this->request['body'];
        $m = new class extends \Model {
            public function update(int $id, array $d): void {
                $this->db->prepare("UPDATE subcategories SET category_id = ?, name = ?, slug = ?, is_active = ? WHERE id = ?")->execute([
                    (int)($d['category_id'] ?? 0), $d['name'] ?? '', $d['slug'] ?? '', isset($d['is_active']) ? (int)$d['is_active'] : 1, $id
                ]);
            }
        };
        $m->update((int)$id, $d);
        \Logger::adminLog((int)$u['sub'], 'update', $d['name'] ?? '', 'subcategories', (int)$id);
        \Response::json(['id' => (int)$id]);
    }
    public function deleteSubcategory(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $m = new class extends \Model {
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM subcategories WHERE id = ?")->execute([$id]);
            }
        };
        $m->delete((int)$id);
        \Logger::adminLog((int)$u['sub'], 'delete', null, 'subcategories', (int)$id);
        \Response::json(['deleted' => true]);
    }
}
