<?php
declare(strict_types=1);

namespace App\Interfaces;

use App\Domain\FileRecord;
use App\Domain\ActivityLog;

interface FileRepositoryInterface {
    public function saveFile(FileRecord $file): int;
    public function getFileById(int $id): ?FileRecord;
    public function getFileByStoredName(string $storedName): ?FileRecord;
    public function getAllFiles(): array;
    public function deleteFile(int $id): bool;
    public function logActivity(ActivityLog $log): void;
    public function getRecentLogs(int $limit = 20): array;
}
