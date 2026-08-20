<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';

class BannerController extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function active(): array {
                $stmt = $this->db->query(
                    "SELECT id, title, subtitle, image, sort_order
                     FROM hero_banners
                     WHERE is_active = 1
                     ORDER BY sort_order ASC, id DESC"
                );
                return $stmt->fetchAll();
            }
        };

        \Response::json($m->active());
    }
}
