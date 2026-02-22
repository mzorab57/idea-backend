<?php
namespace Admin;
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/R2Service.php';
class StorageController extends \Controller {
    public function upload(): void {
        if (!isset($this->request['files']['file'])) {
            \Response::json(['error' => 'No file'], 400);
            return;
        }
        $file = $this->request['files']['file'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            \Response::json(['error' => 'Upload error'], 400);
            return;
        }
        $maxSize = 100 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            \Response::json(['error' => 'File too large'], 413);
            return;
        }
        $type = $this->request['post']['type'] ?? 'uploads';
        $origName = $file['name'] ?? 'file';
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $extLower = strtolower($ext ?? '');
        $allowed = [
            'books' => ['pdf'],
            'thumbnails' => ['jpg', 'jpeg', 'png', 'webp'],
            'uploads' => ['pdf', 'jpg', 'jpeg', 'png', 'webp']
        ];
        if (isset($allowed[$type]) && $extLower && !in_array($extLower, $allowed[$type], true)) {
            \Response::json(['error' => 'Invalid file type'], 415);
            return;
        }
        $key = $this->request['post']['key'] ?? ($type . '/' . uniqid('', true) . ($extLower ? ('.' . $extLower) : ''));
        $contentType = $file['type'] ?? 'application/octet-stream';
        $r2 = new \R2Service();
        $body = fopen($file['tmp_name'], 'rb');
        $res = $r2->upload($key, $body, $contentType);
        \Response::json(['key' => $res['key'], 'etag' => $res['etag']]);
    }
    public function delete(): void {
        $key = $this->request['query']['key'] ?? $this->request['body']['key'] ?? null;
        if (!$key) {
            \Response::json(['error' => 'Key required'], 400);
            return;
        }
        $r2 = new \R2Service();
        if (!$r2->objectExists($key)) {
            \Response::json(['error' => 'Not found'], 404);
            return;
        }
        $r2->delete($key);
        \Response::json(['deleted' => true]);
    }
}
