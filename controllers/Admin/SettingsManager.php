<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
class SettingsManager extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function all(): array {
                $stmt = $this->db->query("SELECT setting_key, setting_value, updated_at FROM settings");
                return $stmt->fetchAll();
            }
        };
        \Response::json($m->all());
    }
    public function update(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $body = $this->request['body'] ?? [];
        $items = [];
        if (is_array($body)) {
            if (isset($body['key']) && array_key_exists('value', $body)) {
                $k = trim((string)$body['key']);
                if ($k !== '') {
                    $items[$k] = $body['value'];
                }
            } elseif (array_is_list($body)) {
                foreach ($body as $entry) {
                    if (is_array($entry) && isset($entry['key']) && array_key_exists('value', $entry)) {
                        $k = trim((string)$entry['key']);
                        if ($k !== '') $items[$k] = $entry['value'];
                    }
                }
            } else {
                $items = $body;
            }
        }
        if (!$items) { \Response::json(['error' => 'Nothing to update'], 400); return; }
        $m = new class extends \Model {
            public function upsert(array $items): void {
                $stmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                foreach ($items as $k => $v) {
                    $stmt->execute([$k, is_string($v) ? $v : json_encode($v)]);
                }
            }
        };
        $m->upsert($items);
        \Logger::adminLog((int)$u['sub'], 'update', null, 'settings', null);
        \Response::json(['updated' => true]);
    }
    public function delete(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        $key = $this->request['query']['key'] ?? ($this->request['body']['key'] ?? '');
        if (!$key) { \Response::json(['error' => 'Key required'], 400); return; }
        $m = new class extends \Model {
            public function remove(string $key): void {
                $this->db->prepare("DELETE FROM settings WHERE setting_key = ?")->execute([$key]);
            }
        };
        $m->remove($key);
        \Logger::adminLog((int)$u['sub'], 'delete', $key, 'settings', null);
        \Response::json(['deleted' => true]);
    }
}
