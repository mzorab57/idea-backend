<?php
namespace PublicC;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/R2Service.php';
class DownloadController extends \Controller {
    public function download(string $id): void {
        $model = new class extends \Model {
            public function findBook(int $id): ?array {
                $stmt = $this->db->prepare("SELECT id, title, file_key FROM books WHERE id = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ?: null;
            }
            public function incDownload(int $id): void {
                $this->db->prepare("UPDATE books SET download_count = download_count + 1 WHERE id = ?")->execute([$id]);
            }
            public function logDownload(int $bookId, string $ip, string $ua): void {
                $stmt = $this->db->prepare("INSERT INTO logs (book_id, action_type, ip_address, user_agent) VALUES (?, 'download', ?, ?)");
                $stmt->execute([$bookId, $ip, $ua]);
            }
        };
        $bookId = (int)$id;
        $book = $model->findBook($bookId);
        if (!$book || !$book['file_key']) {
            \Response::json(['error' => 'Book not found'], 404);
            return;
        }
        $r2 = new \R2Service();
        if (!$r2->objectExists($book['file_key'])) {
            \Response::json(['error' => 'File missing'], 404);
            return;
        }
        $model->incDownload($bookId);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $model->logDownload($bookId, $ip, $ua);
        $ext = pathinfo($book['file_key'], PATHINFO_EXTENSION) ?: 'pdf';
        $base = $book['title'] ?? ('book-' . $bookId);
        $base = preg_replace('/[^A-Za-z0-9\\-\\_\\s]+/', '', $base);
        $base = trim(preg_replace('/\\s+/', '-', $base), '-');
        if ($base === '') $base = 'book-' . $bookId;
        $filename = $base . '.' . $ext;
        $url = $r2->presignedUrl($book['file_key'], 5, $filename);
        \Response::json(['url' => $url, 'expires' => 300]);
    }
}
