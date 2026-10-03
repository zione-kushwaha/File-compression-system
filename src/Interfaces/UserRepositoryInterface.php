<?php
declare(strict_types=1);

namespace App\Interfaces;

use App\Domain\User;

interface UserRepositoryInterface {
    public function createUser(User $user): int;
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;
    public function findById(int $id): ?User;
}
