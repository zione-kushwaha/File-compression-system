<?php
declare(strict_types=1);

namespace App\Domain;

class ActivityLog {
    public ?int $id;
    public string $action;
    public ?int $fileId;
    public string $filename;
    public string $status;
    public ?string $ipAddress;
    public ?string $details;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        string $action,
        ?int $fileId,
        string $filename,
        string $status,
        ?string $ipAddress = null,
        ?string $details = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->action = $action;
        $this->fileId = $fileId;
        $this->filename = $filename;
        $this->status = $status;
        $this->ipAddress = $ipAddress;
        $this->details = $details;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'fileId' => $this->fileId,
            'filename' => $this->filename,
            'status' => $this->status,
            'ipAddress' => $this->ipAddress,
            'details' => $this->details,
            'createdAt' => $this->createdAt,
        ];
    }
}
