<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';

class BannerManager extends \Controller {
    public function list(): void {
        $m = new class extends \Model {
            public function all(): array {
                $stmt = $this->db->query(
                    "SELECT id, title, subtitle, image, sort_order, is_active, created_at, updated_at
                     FROM hero_banners
                     ORDER BY sort_order ASC, id DESC"
                );
                return $stmt->fetchAll();
            }
        };

        \Response::json($m->all());
    }

    public function create(): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') !== 'admin') {
            \Response::json(['error' => 'Forbidden'], 403);
            return;
        }

        $d = $this->request['body'] ?? [];
        $image = trim((string)($d['image'] ?? ''));
        if ($image === '') {
            \Response::json(['error' => 'Image is required'], 400);
            return;
        }

        $m = new class extends \Model {
            public function create(array $d): int {
                $stmt = $this->db->prepare(
                    "INSERT INTO hero_banners (title, subtitle, image, sort_order, is_active)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    trim((string)($d['title'] ?? '')) ?: null,
                    trim((string)($d['subtitle'] ?? '')) ?: null,
                    trim((string)($d['image'] ?? '')),
                    (int)($d['sort_order'] ?? 0),
                    isset($d['is_active']) ? (int)$d['is_active'] : 1,
                ]);
                return (int)$this->db->lastInsertId();
            }
        };

        $id = $m->create($d);
        \Logger::adminLog((int)$u['sub'], 'create', $d['title'] ?? 'Hero banner', 'hero_banners', $id);
        \Response::json(['id' => $id], 201);
    }

    public function update(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') !== 'admin') {
            \Response::json(['error' => 'Forbidden'], 403);
            return;
        }

        $d = $this->request['body'] ?? [];
        $image = trim((string)($d['image'] ?? ''));
        if ($image === '') {
            \Response::json(['error' => 'Image is required'], 400);
            return;
        }

        $m = new class extends \Model {
            public function update(int $id, array $d): void {
                $stmt = $this->db->prepare(
                    "UPDATE hero_banners
                     SET title = ?, subtitle = ?, image = ?, sort_order = ?, is_active = ?
                     WHERE id = ?"
                );
                $stmt->execute([
                    trim((string)($d['title'] ?? '')) ?: null,
                    trim((string)($d['subtitle'] ?? '')) ?: null,
                    trim((string)($d['image'] ?? '')),
                    (int)($d['sort_order'] ?? 0),
                    isset($d['is_active']) ? (int)$d['is_active'] : 1,
                    $id,
                ]);
            }
        };

        $m->update((int)$id, $d);
        \Logger::adminLog((int)$u['sub'], 'update', $d['title'] ?? 'Hero banner', 'hero_banners', (int)$id);
        \Response::json(['id' => (int)$id]);
    }

    public function delete(string $id): void {
        $u = $GLOBALS['auth_user'] ?? null;
        if (($u['role'] ?? '') !== 'admin') {
            \Response::json(['error' => 'Forbidden'], 403);
            return;
        }

        $m = new class extends \Model {
            public function delete(int $id): void {
                $this->db->prepare("DELETE FROM hero_banners WHERE id = ?")->execute([$id]);
            }
        };

        $m->delete((int)$id);
        \Logger::adminLog((int)$u['sub'], 'delete', 'Hero banner', 'hero_banners', (int)$id);
        \Response::json(['deleted' => true]);
    }
}
