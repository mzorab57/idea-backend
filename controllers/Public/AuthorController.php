<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class AuthorController extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function all(): array {
                $stmt = $this->db->query("SELECT id, name, slug, bio, image FROM authors WHERE is_active = 1 ORDER BY name ASC");
                return $stmt->fetchAll();
            }
        };
        \Response::json($m->all());
    }
    public function show(string $id): void {
        $m = new class extends \Model {
            public function author(int $id): ?array {
                $stmt = $this->db->prepare("SELECT id, name, slug, bio, image FROM authors WHERE id = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function books(int $id): array {
                $stmt = $this->db->prepare("SELECT b.id, b.title, b.slug, b.thumbnail FROM books b JOIN book_authors ba ON b.id = ba.book_id WHERE ba.author_id = ? AND b.is_active = 1 ORDER BY b.id DESC");
                $stmt->execute([$id]);
                return $stmt->fetchAll();
            }
        };
        $author = $m->author((int)$id);
        if (!$author) {
            \Response::json(['error' => 'Not found'], 404);
            return;
        }
        $author['books'] = $m->books((int)$id);
        \Response::json($author);
    }
}
