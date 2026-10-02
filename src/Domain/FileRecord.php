<?php
declare(strict_types=1);

namespace App\Domain;

class FileRecord {
    public ?int $id;
    public ?int $userId;
    public string $originalName;
    public string $storedName;
    public string $mimeType;
    public string $algorithm;
    public bool $isEncrypted;
    public ?string $encryptionAlgorithm;
    public int $originalSize;
    public int $compressedSize;
    public float $compressionRatio;
    public string $sha256Checksum;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        ?int $userId,
        string $originalName,
        string $storedName,
        string $mimeType,
        string $algorithm,
        bool $isEncrypted,
        ?string $encryptionAlgorithm,
        int $originalSize,
        int $compressedSize,
        float $compressionRatio,
        string $sha256Checksum,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->originalName = $originalName;
        $this->storedName = $storedName;
        $this->mimeType = $mimeType;
        $this->algorithm = $algorithm;
        $this->isEncrypted = $isEncrypted;
        $this->encryptionAlgorithm = $encryptionAlgorithm;
        $this->originalSize = $originalSize;
        $this->compressedSize = $compressedSize;
        $this->compressionRatio = $compressionRatio;
        $this->sha256Checksum = $sha256Checksum;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'originalName' => $this->originalName,
            'storedName' => $this->storedName,
            'mimeType' => $this->mimeType,
            'algorithm' => $this->algorithm,
            'isEncrypted' => $this->isEncrypted,
            'encryptionAlgorithm' => $this->encryptionAlgorithm,
            'originalSize' => $this->originalSize,
            'compressedSize' => $this->compressedSize,
            'compressionRatio' => $this->compressionRatio,
            'sha256Checksum' => $this->sha256Checksum,
            'createdAt' => $this->createdAt,
        ];
    }
}
