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
    public function listSubcategories(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $q = $_GET['q'] ?? null;
        $active = isset($_GET['active']) ? (int)$_GET['active'] : null;
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $m = new class extends \Model {
            public function list(array $filters, int $limit, int $offset): array {
                $sql = "SELECT s.id, s.category_id, s.name, s.slug, s.is_active, c.name AS category_name
                        FROM subcategories s
                        LEFT JOIN categories c ON s.category_id = c.id
                        WHERE 1=1";
                $params = [];
                if (isset($filters['q']) && $filters['q'] !== null && $filters['q'] !== '') {
                    $sql .= " AND (s.name LIKE ? OR s.slug LIKE ? OR c.name LIKE ?)";
                    $like = '%' . $filters['q'] . '%';
                    $params[] = $like; $params[] = $like; $params[] = $like;
                }
                if (isset($filters['active'])) { $sql .= " AND s.is_active = ?"; $params[] = (int)$filters['active']; }
                if (isset($filters['category_id'])) { $sql .= " AND s.category_id = ?"; $params[] = (int)$filters['category_id']; }
                $sql .= " ORDER BY s.id DESC LIMIT ? OFFSET ?";
                $params[] = $limit; $params[] = $offset;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            }
            public function count(array $filters): int {
                $sql = "SELECT COUNT(*) AS c
                        FROM subcategories s
                        LEFT JOIN categories c ON s.category_id = c.id
                        WHERE 1=1";
                $params = [];
                if (isset($filters['q']) && $filters['q'] !== null && $filters['q'] !== '') {
                    $sql .= " AND (s.name LIKE ? OR s.slug LIKE ? OR c.name LIKE ?)";
                    $like = '%' . $filters['q'] . '%';
                    $params[] = $like; $params[] = $like; $params[] = $like;
                }
                if (isset($filters['active'])) { $sql .= " AND s.is_active = ?"; $params[] = (int)$filters['active']; }
                if (isset($filters['category_id'])) { $sql .= " AND s.category_id = ?"; $params[] = (int)$filters['category_id']; }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch();
                return (int)($row['c'] ?? 0);
            }
        };
        $filters = [];
        if ($q !== null && $q !== '') $filters['q'] = $q;
        if ($active !== null) $filters['active'] = $active;
        if ($categoryId !== null) $filters['category_id'] = $categoryId;
        $items = $m->list($filters, $limit, $offset);
        $total = $m->count($filters);
        \Response::json(['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total]);
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
