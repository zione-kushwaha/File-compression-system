<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Domain\FileRecord;
use App\Domain\ActivityLog;
use App\Interfaces\FileRepositoryInterface;
use App\Services\HuffmanEngine;
use App\Services\ZipEngine;
use App\Services\AesEncryptionService;
use App\Services\FileStorageService;
use RuntimeException;
use Throwable;

class CompressionController {
    private FileRepositoryInterface $repository;
    private HuffmanEngine $huffmanEngine;
    private ZipEngine $zipEngine;
    private AesEncryptionService $encryptionService;
    private FileStorageService $storageService;
    private ?\App\Services\AuthService $authService;

    public function __construct(
        FileRepositoryInterface $repository,
        HuffmanEngine $huffmanEngine,
        ZipEngine $zipEngine,
        AesEncryptionService $encryptionService,
        FileStorageService $storageService,
        ?\App\Services\AuthService $authService = null
    ) {
        $this->repository = $repository;
        $this->huffmanEngine = $huffmanEngine;
        $this->zipEngine = $zipEngine;
        $this->encryptionService = $encryptionService;
        $this->storageService = $storageService;
        $this->authService = $authService;
    }

    public function handleCompress(array $fileUpload, string $algorithm, ?string $password): array {
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

        // Select compression engine
        $engine = ($algorithm === 'zip') ? $this->zipEngine : $this->huffmanEngine;
        $compressionResult = $engine->compress($rawData);
        $dataToStore = $compressionResult['compressedData'];

        $isEncrypted = false;
        $encAlgoName = null;
        if (!empty($password)) {
            $dataToStore = $this->encryptionService->encrypt($dataToStore, $password);
            $isEncrypted = true;
            $encAlgoName = $this->encryptionService->getAlgorithmName();
        }

        $compressedSize = strlen($dataToStore);
        $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 2);

        // Generate safe unique stored filename
        $ext = ($algorithm === 'zip') ? '.szip' : '.shuf';
        $storedName = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName) . $ext;

        $this->storageService->saveCompressed($storedName, $dataToStore);

        $currentUserId = $this->authService?->getCurrentUser()?->id ?? 1;

        $fileRecord = new FileRecord(
            null,
            $currentUserId,
            $originalName,
            $storedName,
            $mimeType,
            $engine->getName(),
            $isEncrypted,
            $encAlgoName,
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
            "Algorithm: {$engine->getName()}, Encrypted: " . ($isEncrypted ? 'YES' : 'NO') . ", Ratio: {$compressionRatio}%"
        ));

        return [
            'success' => true,
            'file' => $fileRecord->toArray(),
            'stats' => $compressionResult['stats'],
            'codebook' => $compressionResult['codebook'],
            'tree' => $compressionResult['tree'],
            'downloadUrl' => "api.php?action=download&id={$fileId}"
        ];
    }

    public function handleDecompress(?array $fileUpload, ?int $fileId, ?string $password): array {
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
            $originalFileName = preg_replace('/(\.shuf|\.szip)$/i', '', basename($fileUpload['name']));
        } else {
            throw new RuntimeException("Please provide a file to decompress.");
        }

        // Check if data is encrypted (starts with ENC1)
        if (str_starts_with($dataToDecompress, 'ENC1')) {
            if (empty($password)) {
                return [
                    'success' => false,
                    'isPasswordRequired' => true,
                    'message' => 'This archive is protected with AES-256 encryption. Please provide the decryption password.'
                ];
            }
            $dataToDecompress = $this->encryptionService->decrypt($dataToDecompress, $password);
        }

        // Determine algorithm from magic header
        $decompressed = '';
        $detectedAlgorithm = '';

        if (str_starts_with($dataToDecompress, 'HUF1')) {
            $decompressed = $this->huffmanEngine->decompress($dataToDecompress);
            $detectedAlgorithm = 'Huffman';
        } elseif (str_starts_with($dataToDecompress, 'ZIP1')) {
            $decompressed = $this->zipEngine->decompress($dataToDecompress);
            $detectedAlgorithm = 'Deflate (Zip)';
        } else {
            throw new RuntimeException("Unrecognized compression format or wrong password.");
        }

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
            "Algorithm: {$detectedAlgorithm}, Integrity: " . ($integrityMatch ? 'VALID' : 'HASH MISMATCH')
        ));

        return [
            'success' => true,
            'originalName' => $originalFileName,
            'restoredSize' => strlen($decompressed),
            'algorithm' => $detectedAlgorithm,
            'sha256Checksum' => $decompressedHash,
            'integrityVerified' => $integrityMatch,
            'downloadUrl' => "api.php?action=download_decompressed&file=" . urlencode($restoredFilename) . "&name=" . urlencode($originalFileName)
        ];
    }
}
