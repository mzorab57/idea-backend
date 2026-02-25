<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class DevController extends \Controller {
    public function resetAdmin(): void {
        $env = $_ENV['APP_ENV'] ?? 'local';
        if ($env !== 'local') {
            \Response::json(['error' => 'Forbidden'], 403);
            return;
        }
        $new = $_GET['pw'] ?? 'admin123';
        $m = new class extends \Model {
            public function reset(string $email, string $new): bool {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                return $this->db->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'")->execute([$hash, $email]);
            }
        };
        $ok = $m->reset('admin@idea.foundation', $new);
        \Response::json(['reset' => $ok]);
    }
}
