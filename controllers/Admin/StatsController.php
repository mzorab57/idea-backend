<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
class StatsController extends \Controller {
    public function summary(): void {
        $m = new class extends \Model {
            public function get(): array {
                $stmt = $this->db->query("SELECT * FROM dashboard_summary");
                return $stmt->fetch() ?: [];
            }
        };
        \Response::json($m->get());
    }
    public function activity(): void {
        $m = new class extends \Model {
            public function recentDownload(): ?array {
                $sql = "SELECT b.title AS title, COUNT(*) AS count, MAX(l.created_at) AS last_at
                        FROM logs l
                        JOIN books b ON l.book_id = b.id
                        WHERE l.action_type = 'download' AND l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        GROUP BY b.id
                        ORDER BY last_at DESC, count DESC
                        LIMIT 1";
                $stmt = $this->db->query($sql);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function viewsLastHour(): array {
                $sql = "SELECT COUNT(*) AS count, MAX(created_at) AS last_at
                        FROM logs
                        WHERE action_type = 'view' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
                $stmt = $this->db->query($sql);
                $row = $stmt->fetch();
                return $row ?: ['count' => 0, 'last_at' => null];
            }
        };
        \Response::json([
            'recent_download' => $m->recentDownload(),
            'views_last_hour' => $m->viewsLastHour(),
        ]);
    }
    public function metrics(): void {
        $period = $_GET['period'] ?? '1d';
        $type = $_GET['type'] ?? 'downloads';
        $interval = null;
        if ($period === '1d') $interval = '1 DAY';
        elseif ($period === '7d') $interval = '7 DAY';
        elseif ($period === '30d') $interval = '30 DAY';
        elseif ($period === 'total') $interval = null;
        $m = new class extends \Model {
            public function countLogsBy(string $action, ?string $interval): int {
                if ($interval) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM logs WHERE action_type = ? AND created_at >= DATE_SUB(NOW(), INTERVAL {$interval})");
                    $stmt->execute([$action]);
                } else {
                    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM logs WHERE action_type = ?");
                    $stmt->execute([$action]);
                }
                $row = $stmt->fetch();
                return (int)($row['c'] ?? 0);
            }
            public function countBooks(?string $interval): int {
                if ($interval) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM books WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL {$interval})");
                    $stmt->execute();
                } else {
                    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM books WHERE is_active = 1");
                    $stmt->execute();
                }
                $row = $stmt->fetch();
                return (int)($row['c'] ?? 0);
            }
        };
        $count = 0;
        if ($type === 'downloads') {
            $count = $m->countLogsBy('download', $interval);
        } elseif ($type === 'views') {
            $count = $m->countLogsBy('view', $interval);
        } elseif ($type === 'books') {
            $count = $m->countBooks($interval);
        } else {
            $count = 0;
        }
        \Response::json([
            'period' => $period,
            'type' => $type,
            'count' => $count,
        ]);
    }
    public function overview(): void {
        $days = max(7, min(90, (int)($_GET['days'] ?? 30)));
        $m = new class extends \Model {
            public function overview(int $days): array {
                $sql = "SELECT DATE(created_at) AS day,
                               SUM(CASE WHEN action_type = 'download' THEN 1 ELSE 0 END) AS downloads,
                               SUM(CASE WHEN action_type = 'view' THEN 1 ELSE 0 END) AS views
                        FROM logs
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        GROUP BY day
                        ORDER BY day ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$days]);
                return $stmt->fetchAll();
            }
        };
        \Response::json(['days' => $days, 'items' => $m->overview($days)]);
    }
}
