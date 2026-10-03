<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Interfaces\FileRepositoryInterface;
use App\Services\FileStorageService;
use App\Domain\ActivityLog;

class FileController {
    private FileRepositoryInterface $repository;
    private FileStorageService $storage;

    public function __construct(FileRepositoryInterface $repository, FileStorageService $storage) {
        $this->repository = $repository;
        $this->storage = $storage;
    }

    public function listFiles(): array {
        $files = $this->repository->getAllFiles();
        return [
            'success' => true,
            'files' => array_map(fn($f) => $f->toArray(), $files),
            'database' => Database::getDriverName()
        ];
    }

    public function downloadFile(int $id): void {
        $file = $this->repository->getFileById($id);
        if (!$file) {
            http_response_code(404);
            die("File not found");
        }

        $path = COMPRESSED_PATH . '/' . $file->storedName;
        if (!file_exists($path)) {
            http_response_code(404);
            die("Physical file missing from disk");
        }

        $this->repository->logActivity(new ActivityLog(
            null,
            'DOWNLOAD',
            $file->id,
            $file->originalName,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "Downloaded archive: {$file->storedName}"
        ));

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file->storedName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function downloadDecompressed(string $filename, string $displayName): void {
        $safeName = basename($filename);
        $path = DECOMPRESSED_PATH . '/' . $safeName;
        if (!file_exists($path)) {
            http_response_code(404);
            die("Decompressed file expired or missing.");
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($displayName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function deleteFile(int $id): array {
        $file = $this->repository->getFileById($id);
        if (!$file) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $this->storage->delete($file->storedName);
        $this->repository->deleteFile($id);

        $this->repository->logActivity(new ActivityLog(
            null,
            'DELETE',
            $id,
            $file->originalName,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "Deleted archive from database and storage"
        ));

        return ['success' => true];
    }

    public function getLogs(): array {
        $logs = $this->repository->getRecentLogs(30);
        return [
            'success' => true,
            'logs' => array_map(fn($l) => $l->toArray(), $logs)
        ];
    }

    public function getSystemInfo(): array {
        return [
            'success' => true,
            'phpVersion' => PHP_VERSION,
            'database' => Database::getDriverName(),
            'openSsl' => extension_loaded('openssl'),
            'zip' => extension_loaded('zip'),
            'pdoMysql' => extension_loaded('pdo_mysql'),
            'maxUpload' => ini_get('upload_max_filesize'),
            'algorithms' => [
                'huffman' => [
                    'name' => 'Huffman Variable-Length Coding',
                    'category' => 'Entropy Lossless Compression',
                    'description' => 'Optimal prefix coding based on symbol frequency min-heap.'
                ],
                'zip' => [
                    'name' => 'DEFLATE (RFC 1951 / ZIP)',
                    'category' => 'Dictionary + Huffman Compression',
                    'description' => 'Combines LZ77 and Huffman coding.'
                ]
            ],
            'encryption' => [
                'name' => 'AES-256-CBC with PBKDF2 (15,000 iterations) and HMAC-SHA256 Authenticated Encryption',
                'standard' => 'NIST SP 800-38A / RFC 2898'
            ]
        ];
    }
}
