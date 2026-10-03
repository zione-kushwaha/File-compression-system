<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Domain\User;
use App\Interfaces\UserRepositoryInterface;
use PDO;

class DatabaseUserRepository implements UserRepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function createUser(User $user): int {
        $sql = "INSERT INTO users (username, email, password_hash, created_at)
                VALUES (:username, :email, :password_hash, datetime('now'))";

        if (Database::getDriverName() === 'mysql') {
            $sql = str_replace("datetime('now')", "NOW()", $sql);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username' => $user->username,
            ':email' => $user->email,
            ':password_hash' => $user->passwordHash,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ? $this->mapToUser($row) : null;
    }

    public function findByUsername(string $username): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ? $this->mapToUser($row) : null;
    }

    public function findById(int $id): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapToUser($row) : null;
    }

    private function mapToUser(array $row): User {
        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password_hash'],
            $row['created_at']
        );
    }
}
