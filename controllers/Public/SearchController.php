<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class SearchController extends \Controller {
    public function search(): void {
        $q = $_GET['q'] ?? '';
        $m = new class extends \Model {
            public function books(string $q): array {
                $stmt = $this->db->prepare("SELECT id, title, slug, thumbnail FROM books WHERE is_active = 1 AND MATCH(title, short_description) AGAINST (? IN NATURAL LANGUAGE MODE) ORDER BY id DESC LIMIT 20");
                $stmt->execute([$q]);
                return $stmt->fetchAll();
            }
            public function authors(string $q): array {
                $stmt = $this->db->prepare("SELECT id, name, slug, image FROM authors WHERE is_active = 1 AND MATCH(name) AGAINST (? IN NATURAL LANGUAGE MODE) ORDER BY id DESC LIMIT 20");
                $stmt->execute([$q]);
                return $stmt->fetchAll();
            }
        };
        \Response::json(['books' => $m->books($q), 'authors' => $m->authors($q)]);
    }
}
