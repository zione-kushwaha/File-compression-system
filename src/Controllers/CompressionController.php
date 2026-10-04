<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Domain\FileRecord;
use App\Domain\ActivityLog;
use App\Interfaces\FileRepositoryInterface;
use App\Services\HuffmanEngine;
use App\Services\FileStorageService;
use RuntimeException;

/**
 * CompressionController
 * Handles file compression and decompression using pure Huffman Coding.
 */
class CompressionController {
    private FileRepositoryInterface $repository;
    private HuffmanEngine $huffmanEngine;
    private FileStorageService $storageService;
    private ?\App\Services\AuthService $authService;

    public function __construct(
        FileRepositoryInterface $repository,
        HuffmanEngine $huffmanEngine,
        FileStorageService $storageService,
        ?\App\Services\AuthService $authService = null
    ) {
        $this->repository = $repository;
        $this->huffmanEngine = $huffmanEngine;
        $this->storageService = $storageService;
        $this->authService = $authService;
    }

    public function handleCompress(array $fileUpload): array {
        if (!isset($fileUpload['tmp_name']) || !is_uploaded_file($fileUpload['tmp_name'])) {
            throw new RuntimeException("No valid file uploaded.");
        }

        if ($fileUpload['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload error code: " . $fileUpload['error']);
        }

        $originalName = basename($fileUpload['name']);
        $rawData = file_get_contents($fileUpload['tmp_name']);
        if ($rawData === false || strlen($rawData) === 0) {
            throw new RuntimeException("Uploaded file is empty or unreadable.");
        }

        $originalSize = strlen($rawData);
        $originalHash = hash('sha256', $rawData);
        $mimeType = mime_content_type($fileUpload['tmp_name']) ?: 'application/octet-stream';

        // Perform Huffman lossless compression
        $compressionResult = $this->huffmanEngine->compress($rawData);
        $dataToStore = $compressionResult['compressedData'];

        $compressedSize = strlen($dataToStore);
        $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 2);

        // Generate safe unique stored filename with .shuf extension
        $storedName = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName) . '.shuf';
        $this->storageService->saveCompressed($storedName, $dataToStore);

        $currentUserId = $this->authService?->getCurrentUser()?->id ?? 1;

        $fileRecord = new FileRecord(
            null,
            $currentUserId,
            $originalName,
            $storedName,
            $mimeType,
            'Huffman',
            false,
            null,
            $originalSize,
            $compressedSize,
            $compressionRatio,
            $originalHash
        );

        $fileId = $this->repository->saveFile($fileRecord);
        $fileRecord->id = $fileId;

        // Log activity
        $this->repository->logActivity(new ActivityLog(
            null,
            'COMPRESS',
            $fileId,
            $originalName,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "Algorithm: Huffman Coding, Saved: {$compressionRatio}%"
        ));

        return [
            'success' => true,
            'file' => $fileRecord->toArray(),
            'stats' => $compressionResult['stats'] ?? [],
            'codebook' => $compressionResult['codebook'] ?? [],
            'downloadUrl' => "api.php?action=download&id={$fileId}"
        ];
    }

    public function handleDecompress(?array $fileUpload, ?int $fileId): array {
        $dataToDecompress = '';
        $originalFileName = 'restored_file';
        $expectedHash = null;

        if ($fileId !== null && $fileId > 0) {
            $fileRecord = $this->repository->getFileById($fileId);
            if (!$fileRecord) {
                throw new RuntimeException("File record not found in system.");
            }
            $dataToDecompress = $this->storageService->getCompressed($fileRecord->storedName);
            $originalFileName = $fileRecord->originalName;
            $expectedHash = $fileRecord->sha256Checksum;
        } elseif ($fileUpload !== null && isset($fileUpload['tmp_name']) && is_uploaded_file($fileUpload['tmp_name'])) {
            $dataToDecompress = file_get_contents($fileUpload['tmp_name']);
            $originalFileName = preg_replace('/(\.shuf)$/i', '', basename($fileUpload['name']));
        } else {
            throw new RuntimeException("Please provide a .shuf file to decompress.");
        }

        // Decompress using Huffman Engine
        if (!str_starts_with($dataToDecompress, 'HUF1')) {
            throw new RuntimeException("Invalid file format. Expected a valid Huffman archive (.shuf).");
        }

        $decompressed = $this->huffmanEngine->decompress($dataToDecompress);
        $decompressedHash = hash('sha256', $decompressed);
        $integrityMatch = ($expectedHash !== null) ? hash_equals($expectedHash, $decompressedHash) : true;

        // Save restored file
        $restoredFilename = 'restored_' . bin2hex(random_bytes(4)) . '_' . $originalFileName;
        $this->storageService->saveDecompressed($restoredFilename, $decompressed);

        $this->repository->logActivity(new ActivityLog(
            null,
            'DECOMPRESS',
            $fileId,
            $originalFileName,
            $integrityMatch ? 'SUCCESS' : 'FAILED',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "Algorithm: Huffman Coding, Integrity: " . ($integrityMatch ? 'VALID' : 'HASH MISMATCH')
        ));

        return [
            'success' => true,
            'originalName' => $originalFileName,
            'restoredSize' => strlen($decompressed),
            'algorithm' => 'Huffman Coding',
            'sha256Checksum' => $decompressedHash,
            'integrityVerified' => $integrityMatch,
            'downloadUrl' => "api.php?action=download_decompressed&file=" . urlencode($restoredFilename) . "&name=" . urlencode($originalFileName)
        ];
    }
}
