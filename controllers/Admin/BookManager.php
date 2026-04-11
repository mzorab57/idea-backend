<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
require_once __DIR__ . '/../../utils/Validator.php';
require_once __DIR__ . '/../../utils/Permission.php';
class BookManager extends \Controller {
    public function create(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') === 'employee' && !\Permission::can((int)$u['sub'], 'books', 'create')) { \Response::json(['error' => 'Forbidden'], 403); return; }
        $data = $this->request['body'];
        $reqErrors = \Validator::require(['title' => $data['title'] ?? '', 'slug' => $data['slug'] ?? ''], ['title', 'slug']);
        if ($reqErrors) { \Response::json(['error' => 'Invalid input', 'details' => $reqErrors], 400); return; }
        $model = new class extends \Model {
            public function slugExists(string $slug): bool {
                $stmt = $this->db->prepare("SELECT id FROM books WHERE slug = ? LIMIT 1");
                $stmt->execute([$slug]);
                return (bool)$stmt->fetch();
            }
            public function create(array $data, int $userId): int {
                try {
                    $this->db->beginTransaction();
                    $stmt = $this->db->prepare("INSERT INTO books (title, slug, short_description, long_description, category_id, subcategory_id, file_key, thumbnail, youtube_url, is_featured, is_active, meta_title, meta_description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $data['title'] ?? null,
                        $data['slug'] ?? null,
                        $data['short_description'] ?? null,
                        $data['long_description'] ?? null,
                        $data['category_id'] ?? null,
                        $data['subcategory_id'] ?? null,
                        $data['file_key'] ?? null,
                        $data['thumbnail'] ?? null,
                        $data['youtube_url'] ?? null,
                        isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
                        isset($data['is_active']) ? (int)$data['is_active'] : 1,
                        $data['meta_title'] ?? null,
                        $data['meta_description'] ?? null,
                        $userId
                    ]);
                    $bookId = (int)$this->db->lastInsertId();
                    $authors = $data['authors'] ?? [];
                    foreach ($authors as $a) {
                        $this->db->prepare("INSERT INTO book_authors (book_id, author_id, role) VALUES (?, ?, ?)")->execute([$bookId, (int)$a['id'], $a['role'] ?? 'author']);
                    }
                    $specs = $data['specifications'] ?? [];
                    foreach ($specs as $s) {
                        $this->db->prepare("INSERT INTO book_specifications (book_id, spec_name, spec_value, `group`, is_visible) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$bookId, $s['name'] ?? '', $s['value'] ?? '', $s['group'] ?? null, isset($s['is_visible']) ? (int)$s['is_visible'] : 1]);
                    }
                    $this->db->commit();
                    return $bookId;
                } catch (\Throwable $e) {
                    if ($this->db->inTransaction()) $this->db->rollBack();
                    throw $e;
                }
            }
            public function update(int $id, array $data): void {
                try {
                    $this->db->beginTransaction();
                    $this->db->prepare("UPDATE books SET title = ?, slug = ?, short_description = ?, long_description = ?, category_id = ?, subcategory_id = ?, file_key = ?, thumbnail = ?, youtube_url = ?, is_featured = ?, is_active = ?, meta_title = ?, meta_description = ? WHERE id = ?")
                        ->execute([
                            $data['title'] ?? null,
                            $data['slug'] ?? null,
                            $data['short_description'] ?? null,
                            $data['long_description'] ?? null,
                            $data['category_id'] ?? null,
                            $data['subcategory_id'] ?? null,
                            $data['file_key'] ?? null,
                            $data['thumbnail'] ?? null,
                            $data['youtube_url'] ?? null,
                            isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
                            isset($data['is_active']) ? (int)$data['is_active'] : 1,
                            $data['meta_title'] ?? null,
                            $data['meta_description'] ?? null,
                            $id
                        ]);
                    $this->db->prepare("DELETE FROM book_authors WHERE book_id = ?")->execute([$id]);
                    $authors = $data['authors'] ?? [];
                    foreach ($authors as $a) {
                        $this->db->prepare("INSERT INTO book_authors (book_id, author_id, role) VALUES (?, ?, ?)")->execute([$id, (int)$a['id'], $a['role'] ?? 'author']);
                    }
                    $this->db->prepare("DELETE FROM book_specifications WHERE book_id = ?")->execute([$id]);
                    $specs = $data['specifications'] ?? [];
                    foreach ($specs as $s) {
                        $this->db->prepare("INSERT INTO book_specifications (book_id, spec_name, spec_value, `group`, is_visible) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$id, $s['name'] ?? '', $s['value'] ?? '', $s['group'] ?? null, isset($s['is_visible']) ? (int)$s['is_visible'] : 1]);
                    }
                    $this->db->commit();
                } catch (\Throwable $e) {
                    if ($this->db->inTransaction()) $this->db->rollBack();
                    throw $e;
                }
            }
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
            }
            public function find(int $id): ?array {
                $stmt = $this->db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
        };
        try {
            if ($model->slugExists($data['slug'])) { \Response::json(['error' => 'Slug already exists'], 409); return; }
            $bookId = $model->create($data, (int)$u['sub']);
            \Logger::adminLog((int)$u['sub'], 'create', $data['title'] ?? '', 'books', $bookId);
            \Response::json(['id' => $bookId], 201);
        } catch (\Throwable $e) {
            \Response::json(['error' => 'Failed to create'], 400);
        }
    }
    public function update(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') === 'employee' && !\Permission::can((int)$u['sub'], 'books', 'update')) { \Response::json(['error' => 'Forbidden'], 403); return; }
        $data = $this->request['body'];
        $mid = new class extends \Model {
            public function updateBook(int $id, array $data): void {
                $this->db->beginTransaction();
                $this->db->prepare("UPDATE books SET title = ?, slug = ?, short_description = ?, long_description = ?, category_id = ?, subcategory_id = ?, file_key = ?, thumbnail = ?, youtube_url = ?, is_featured = ?, is_active = ?, meta_title = ?, meta_description = ? WHERE id = ?")
                    ->execute([
                        $data['title'] ?? null,
                        $data['slug'] ?? null,
                        $data['short_description'] ?? null,
                        $data['long_description'] ?? null,
                        $data['category_id'] ?? null,
                        $data['subcategory_id'] ?? null,
                        $data['file_key'] ?? null,
                        $data['thumbnail'] ?? null,
                        $data['youtube_url'] ?? null,
                        isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
                        isset($data['is_active']) ? (int)$data['is_active'] : 1,
                        $data['meta_title'] ?? null,
                        $data['meta_description'] ?? null,
                        $id
                    ]);
                $this->db->prepare("DELETE FROM book_authors WHERE book_id = ?")->execute([$id]);
                foreach ($data['authors'] ?? [] as $a) {
                    $this->db->prepare("INSERT INTO book_authors (book_id, author_id, role) VALUES (?, ?, ?)")->execute([$id, (int)$a['id'], $a['role'] ?? 'author']);
                }
                $this->db->prepare("DELETE FROM book_specifications WHERE book_id = ?")->execute([$id]);
                foreach ($data['specifications'] ?? [] as $s) {
                    $this->db->prepare("INSERT INTO book_specifications (book_id, spec_name, spec_value, `group`, is_visible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id, $s['name'] ?? '', $s['value'] ?? '', $s['group'] ?? null, isset($s['is_visible']) ? (int)$s['is_visible'] : 1]);
                }
                $this->db->commit();
            }
        };
        try {
            $bookId = (int)$id;
            $mid->updateBook($bookId, $data);
            \Logger::adminLog((int)$u['sub'], 'update', $data['title'] ?? '', 'books', $bookId);
            \Response::json(['id' => $bookId]);
        } catch (\Throwable $e) {
            \Response::json(['error' => 'Failed to update'], 400);
        }
    }
    public function delete(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') === 'employee') { \Response::json(['error' => 'Forbidden'], 403); return; }
        $deleteFile = isset($_GET['delete_file']) ? (int)$_GET['delete_file'] === 1 : false;
        $mid = new class extends \Model {
            public function getFileKey(int $id): ?string {
                $stmt = $this->db->prepare("SELECT file_key FROM books WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ? ($row['file_key'] ?? null) : null;
            }
            public function deleteBook(int $id): void {
                $this->db->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
            }
        };
        $bookId = (int)$id;
        if ($deleteFile) {
            $key = $mid->getFileKey($bookId);
            if ($key) {
                try {
                    $r2 = new \R2Service();
                    if ($r2->objectExists($key)) $r2->delete($key);
                } catch (\Throwable $e) {
                }
            }
        }
        $mid->deleteBook($bookId);
        \Logger::adminLog((int)$u['sub'], 'delete', null, 'books', $bookId);
        \Response::json(['deleted' => true]);
    }
    public function get(string $id): void {
        $mid = new class extends \Model {
            public function getBook(int $id): ?array {
                $stmt = $this->db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function getAuthors(int $id): array {
                $stmt = $this->db->prepare("SELECT a.id, a.name, ba.role FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchAll();
            }
            public function getSpecs(int $id): array {
                $stmt = $this->db->prepare("SELECT spec_name, spec_value, `group`, is_visible FROM book_specifications WHERE book_id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchAll();
            }
        };
        $book = $mid->getBook((int)$id);
        if (!$book) {
            \Response::json(['error' => 'Not found'], 404);
            return;
        }
        $book['authors'] = $mid->getAuthors((int)$id);
        $book['specifications'] = $mid->getSpecs((int)$id);
        \Response::json($book);
    }
    public function list(): void {
        $filters = [];
        if (isset($_GET['q'])) $filters['q'] = $_GET['q'];
        if (isset($_GET['active'])) $filters['active'] = (int)$_GET['active'];
        if (isset($_GET['category_id'])) $filters['category_id'] = (int)$_GET['category_id'];
        if (isset($_GET['featured'])) $filters['featured'] = (int)$_GET['featured'];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $mid = new class extends \Model {
            public function list(array $filters, int $limit, int $offset): array {
                $sql = "SELECT 
                            b.*,
                            GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS author_names,
                            c.name AS category_name,
                            s.name AS subcategory_name
                        FROM books b
                        LEFT JOIN book_authors ba ON b.id = ba.book_id
                        LEFT JOIN authors a ON ba.author_id = a.id
                        LEFT JOIN categories c ON b.category_id = c.id
                        LEFT JOIN subcategories s ON b.subcategory_id = s.id
                        WHERE 1=1";
                $params = [];
                if (isset($filters['q'])) { $sql .= " AND MATCH(b.title, b.short_description) AGAINST (? IN NATURAL LANGUAGE MODE)"; $params[] = $filters['q']; }
                if (isset($filters['active'])) { $sql .= " AND b.is_active = ?"; $params[] = (int)$filters['active']; }
                if (isset($filters['category_id'])) { $sql .= " AND b.category_id = ?"; $params[] = (int)$filters['category_id']; }
                if (isset($filters['featured'])) { $sql .= " AND b.is_featured = ?"; $params[] = (int)$filters['featured']; }
                $sql .= " GROUP BY b.id ORDER BY b.id DESC LIMIT ? OFFSET ?";
                $params[] = $limit; $params[] = $offset;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            }
            public function count(array $filters): int {
                $sql = "SELECT COUNT(*) AS c FROM books b WHERE 1=1";
                $params = [];
                if (isset($filters['q'])) { $sql .= " AND MATCH(b.title, b.short_description) AGAINST (? IN NATURAL LANGUAGE MODE)"; $params[] = $filters['q']; }
                if (isset($filters['active'])) { $sql .= " AND b.is_active = ?"; $params[] = (int)$filters['active']; }
                if (isset($filters['category_id'])) { $sql .= " AND b.category_id = ?"; $params[] = (int)$filters['category_id']; }
                if (isset($filters['featured'])) { $sql .= " AND b.is_featured = ?"; $params[] = (int)$filters['featured']; }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch();
                return (int)($row['c'] ?? 0);
            }
        };
        $items = $mid->list($filters, $limit, $offset);
        $total = $mid->count($filters);
        \Response::json(['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total]);
    }
}
