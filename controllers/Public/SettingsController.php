<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class SettingsController extends \Controller {
    public function get(): void {
        $m = new class extends \Model {
            public function settings(): array {
                $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
                $out = [];
                foreach ($stmt->fetchAll() as $row) {
                    $out[$row['setting_key']] = $row['setting_value'];
                }
                return $out;
            }
        };
        \Response::json($m->settings());
    }
}
