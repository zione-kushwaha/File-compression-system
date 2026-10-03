<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Domain\FileRecord;
use App\Domain\ActivityLog;
use App\Interfaces\FileRepositoryInterface;
use PDO;

class DatabaseFileRepository implements FileRepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function saveFile(FileRecord $file): int {
        $sql = "INSERT INTO files (
            user_id, original_name, stored_name, mime_type, algorithm, 
            is_encrypted, encryption_algorithm, original_size, compressed_size, 
            compression_ratio, sha256_checksum, created_at
        ) VALUES (
            :user_id, :original_name, :stored_name, :mime_type, :algorithm,
            :is_encrypted, :encryption_algorithm, :original_size, :compressed_size,
            :compression_ratio, :sha256_checksum, datetime('now')
        )";

        if (Database::getDriverName() === 'mysql') {
            $sql = str_replace("datetime('now')", "NOW()", $sql);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $file->userId,
            ':original_name' => $file->originalName,
            ':stored_name' => $file->storedName,
            ':mime_type' => $file->mimeType,
            ':algorithm' => $file->algorithm,
            ':is_encrypted' => $file->isEncrypted ? 1 : 0,
            ':encryption_algorithm' => $file->encryptionAlgorithm,
            ':original_size' => $file->originalSize,
            ':compressed_size' => $file->compressedSize,
            ':compression_ratio' => $file->compressionRatio,
            ':sha256_checksum' => $file->sha256Checksum,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getFileById(int $id): ?FileRecord {
        $stmt = $this->db->prepare("SELECT * FROM files WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapToFileRecord($row) : null;
    }

    public function getFileByStoredName(string $storedName): ?FileRecord {
        $stmt = $this->db->prepare("SELECT * FROM files WHERE stored_name = :stored_name LIMIT 1");
        $stmt->execute([':stored_name' => $storedName]);
        $row = $stmt->fetch();
        return $row ? $this->mapToFileRecord($row) : null;
    }

    public function getAllFiles(): array {
        $stmt = $this->db->query("SELECT * FROM files ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapToFileRecord'], $rows);
    }

    public function deleteFile(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function logActivity(ActivityLog $log): void {
        $sql = "INSERT INTO activity_logs (action, file_id, filename, status, ip_address, details, created_at)
                VALUES (:action, :file_id, :filename, :status, :ip, :details, datetime('now'))";

        if (Database::getDriverName() === 'mysql') {
            $sql = str_replace("datetime('now')", "NOW()", $sql);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':action' => $log->action,
            ':file_id' => $log->fileId,
            ':filename' => $log->filename,
            ':status' => $log->status,
            ':ip' => $log->ipAddress,
            ':details' => $log->details,
        ]);
    }

    public function getRecentLogs(int $limit = 20): array {
        $stmt = $this->db->prepare("SELECT * FROM activity_logs ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            return new ActivityLog(
                (int)$row['id'],
                $row['action'],
                $row['file_id'] !== null ? (int)$row['file_id'] : null,
                $row['filename'],
                $row['status'],
                $row['ip_address'],
                $row['details'],
                $row['created_at']
            );
        }, $rows);
    }

    private function mapToFileRecord(array $row): FileRecord {
        return new FileRecord(
            (int)$row['id'],
            $row['user_id'] !== null ? (int)$row['user_id'] : null,
            $row['original_name'],
            $row['stored_name'],
            $row['mime_type'],
            $row['algorithm'],
            (bool)$row['is_encrypted'],
            $row['encryption_algorithm'],
            (int)$row['original_size'],
            (int)$row['compressed_size'],
            (float)$row['compression_ratio'],
            $row['sha256_checksum'],
            $row['created_at']
        );
    }
}
