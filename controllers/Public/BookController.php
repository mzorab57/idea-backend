<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';

class BookController extends \Controller {

    public function list(): void {
        $q      = $_GET['q'] ?? null;
        $cat    = isset($_GET['category_id'])    ? (int)$_GET['category_id']    : null;
        $sub    = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $m = new class extends \Model {

            public function fetch(?string $q, ?int $cat, ?int $sub, int $limit, int $offset): array {
                $sql    = "SELECT id, title, slug, thumbnail, short_description, is_featured FROM books WHERE is_active = 1";
                $params = [];
                if ($cat) { $sql .= " AND category_id = ?";    $params[] = $cat; }
                if ($sub) { $sql .= " AND subcategory_id = ?"; $params[] = $sub; }
                if ($q)   { $sql .= " AND MATCH(title, short_description) AGAINST (? IN NATURAL LANGUAGE MODE)"; $params[] = $q; }
                $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            }

            public function featured(): array {
                $stmt = $this->db->query(
                    "SELECT id, title, slug, thumbnail, short_description
                     FROM books
                     WHERE is_active = 1 AND is_featured = 1
                     ORDER BY id DESC LIMIT 20"
                );
                return $stmt->fetchAll();
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

            private function normalizeRole(string $role): string {
                $r = strtolower(trim($role));
                if (in_array($r, ['author', 'writer', 'نووسەر']))                    return 'author';
                if (in_array($r, ['translator', 'translation', 'وەرگێڕ', 'wargeyr'])) return 'translator';
                if (in_array($r, ['editor', 'edit', 'ئیدیتۆر', 'دەستکاریکەر']))      return 'editor';
                return 'author';
            }

            public function detail(int $id): ?array {
                $stmt = $this->db->prepare(
                    "SELECT * FROM full_book_details WHERE id = ? LIMIT 1"
                );
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) return null;

                $authors = [];
                $raw = $row['authors'] ?? null;
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $a) {
                            $name = trim($a['name'] ?? '');
                            if (!$name) continue;
                            $authors[] = [
                                'id'   => $a['id']   ?? null,
                                'name' => $name,
                                'role' => $this->normalizeRole($a['role'] ?? ''),
                            ];
                        }
                    }
                }
                if (empty($authors)) {
                    $j = $this->db->prepare(
                        "SELECT a.id, a.name, COALESCE(ba.role, '') AS role
                         FROM book_authors ba
                         JOIN authors a ON a.id = ba.author_id
                         WHERE ba.book_id = ?
                         ORDER BY
                           CASE LOWER(TRIM(COALESCE(ba.role,'')))
                             WHEN 'author' THEN 1
                             WHEN 'writer' THEN 1
                             WHEN 'translator' THEN 2
                             WHEN 'translation' THEN 2
                             WHEN 'editor' THEN 3
                             WHEN 'edit' THEN 3
                             ELSE 4
                           END, a.name ASC"
                    );
                    $j->execute([$id]);
                    $rows = $j->fetchAll() ?: [];
                    foreach ($rows as $r) {
                        $name = trim($r['name'] ?? '');
                        if (!$name) continue;
                        $authors[] = [
                            'id'   => $r['id'] ?? null,
                            'name' => $name,
                            'role' => $this->normalizeRole($r['role'] ?? ''),
                        ];
                    }
                }
                $row['authors'] = $authors;
                $byRole = ['author' => [], 'translator' => [], 'editor' => []];
                foreach ($authors as $a) {
                    $r = $a['role'] ?? 'author';
                    if (!isset($byRole[$r])) $byRole[$r] = [];
                    $byRole[$r][] = $a['name'];
                }
                if (empty($row['author_names']) && !empty($byRole['author'])) {
                    $row['author_names'] = implode('، ', $byRole['author']);
                }
                if (empty($row['translator_names']) && !empty($byRole['translator'])) {
                    $row['translator_names'] = implode('، ', $byRole['translator']);
                }
                if (empty($row['editor_names']) && !empty($byRole['editor'])) {
                    $row['editor_names'] = implode('، ', $byRole['editor']);
                }
                return $row;
            }

            public function specs(int $id): array {
                $stmt = $this->db->prepare(
                    "SELECT spec_name AS name, spec_value AS value,
                            `group`, is_visible
                     FROM book_specifications
                     WHERE book_id = ? AND is_visible = 1
                     ORDER BY `group` IS NULL, `group`, name"
                );
                $stmt->execute([$id]);
                return $stmt->fetchAll() ?: [];
            }

            public function logView(int $id, string $ip, string $ua): void {
                $this->db->prepare(
                    "INSERT INTO logs (book_id, action_type, ip_address, user_agent)
                     VALUES (?, 'view', ?, ?)"
                )->execute([$id, $ip, $ua]);
                $this->db->prepare(
                    "UPDATE books SET view_count = view_count + 1 WHERE id = ?"
                )->execute([$id]);
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

        $book['specifications'] = $m->specs((int)$id);
        \Response::json($book);
    }
}
