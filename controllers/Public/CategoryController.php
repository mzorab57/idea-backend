<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class CategoryController extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function cats(): array {
                $cats = $this->db->query("SELECT id, name, slug, description FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
                $subs = $this->db->query("SELECT id, category_id, name, slug FROM subcategories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
                return ['categories' => $cats, 'subcategories' => $subs];
            }
        };
        \Response::json($m->cats());
    }
}
