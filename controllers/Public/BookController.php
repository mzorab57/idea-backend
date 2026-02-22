<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class BookController extends \Controller {
    public function list(): void {
        $q = $_GET['q'] ?? null;
        $cat = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $sub = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $m = new class extends \Model {
            public function fetch(?string $q, ?int $cat, ?int $sub, int $limit, int $offset): array {
                $sql = "SELECT id, title, slug, thumbnail, short_description, is_featured FROM books WHERE is_active = 1";
                $params = [];
                if ($cat) { $sql .= " AND category_id = ?"; $params[] = $cat; }
                if ($sub) { $sql .= " AND subcategory_id = ?"; $params[] = $sub; }
                if ($q) { $sql .= " AND MATCH(title, short_description) AGAINST (? IN NATURAL LANGUAGE MODE)"; $params[] = $q; }
                $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            }
            public function featured(): array {
                $stmt = $this->db->query("SELECT id, title, slug, thumbnail, short_description FROM books WHERE is_active = 1 AND is_featured = 1 ORDER BY id DESC LIMIT 20");
                return $stmt->fetchAll();
            }
            public function detail(int $id): ?array {
                $stmt = $this->db->prepare("SELECT * FROM full_book_details WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function logView(int $id, string $ip, string $ua): void {
                $this->db->prepare("INSERT INTO logs (book_id, action_type, ip_address, user_agent) VALUES (?, 'view', ?, ?)")->execute([$id, $ip, $ua]);
                $this->db->prepare("UPDATE books SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
            }
        };
        if (isset($_GET['featured'])) {
            \Response::json($m->featured());
            return;
        }
        $list = $m->fetch($q, $cat, $sub, $limit, $offset);
        \Response::json(['items' => $list, 'page' => $page]);
    }
    public function show(string $id): void {
        $m = new class extends \Model {
            public function detail(int $id): ?array {
                $stmt = $this->db->prepare("SELECT * FROM full_book_details WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function logView(int $id, string $ip, string $ua): void {
                $this->db->prepare("INSERT INTO logs (book_id, action_type, ip_address, user_agent) VALUES (?, 'view', ?, ?)")->execute([$id, $ip, $ua]);
                $this->db->prepare("UPDATE books SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
            }
        };
        $book = $m->detail((int)$id);
        if (!$book) {
            \Response::json(['error' => 'Not found'], 404);
            return;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $m->logView((int)$id, $ip, $ua);
        \Response::json($book);
    }
}
