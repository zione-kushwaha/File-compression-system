<?php
declare(strict_types=1);

namespace App\Domain;

class User {
    public ?int $id;
    public string $username;
    public string $email;
    public string $passwordHash;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        string $username,
        string $email,
        string $passwordHash,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public function toArray(bool $includeHash = false): array {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'createdAt' => $this->createdAt,
        ];
        if ($includeHash) {
            $data['passwordHash'] = $this->passwordHash;
        }
        return $data;
    }
}
