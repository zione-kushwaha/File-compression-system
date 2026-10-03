<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class FileStorageService {
    public function saveCompressed(string $filename, string $data): string {
        $path = COMPRESSED_PATH . '/' . $filename;
        if (file_put_contents($path, $data) === false) {
            throw new RuntimeException("Failed to write compressed file to disk.");
        }
        return $path;
    }

    public function saveDecompressed(string $filename, string $data): string {
        $path = DECOMPRESSED_PATH . '/' . $filename;
        if (file_put_contents($path, $data) === false) {
            throw new RuntimeException("Failed to write decompressed file to disk.");
        }
        return $path;
    }

    public function getCompressed(string $filename): string {
        $path = COMPRESSED_PATH . '/' . basename($filename);
        if (!file_exists($path)) {
            throw new RuntimeException("Compressed file not found: " . htmlspecialchars($filename));
        }
        return file_get_contents($path);
    }

    public function delete(string $filename): bool {
        $pathCompressed = COMPRESSED_PATH . '/' . basename($filename);
        $deleted = true;
        if (file_exists($pathCompressed)) {
            $deleted = unlink($pathCompressed);
        }
        return $deleted;
    }
}
