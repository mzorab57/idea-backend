<?php
require_once __DIR__ . '/../config/r2.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
class R2Service {
    private S3Client $client;
    private string $bucket;
    public function __construct() {
        $cfg = require __DIR__ . '/../config/r2.php';
        $this->bucket = $cfg['bucket'];
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $cfg['region'],
            'endpoint' => $cfg['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $cfg['access_key_id'],
                'secret' => $cfg['secret_access_key']
            ]
        ]);
    }
    public function presignedUrl(string $key, int $expiresMinutes = 5, ?string $downloadName = null): string {
        $params = ['Bucket' => $this->bucket, 'Key' => $key];
        if ($downloadName) {
            $params['ResponseContentDisposition'] = 'attachment; filename="' . $downloadName . '"';
        }
        $cmd = $this->client->getCommand('GetObject', $params);
        $req = $this->client->createPresignedRequest($cmd, "+{$expiresMinutes} minutes");
        return (string)$req->getUri();
    }
    public function objectExists(string $key): bool {
        try {
            $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $key]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    public function upload(string $key, $body, string $contentType = 'application/octet-stream'): array {
        $result = $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $contentType
        ]);
        return [
            'key' => $key,
            'etag' => (string)($result['ETag'] ?? ''),
            'version_id' => (string)($result['VersionId'] ?? '')
        ];
    }
    public function delete(string $key): void {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $key]);
    }
}
