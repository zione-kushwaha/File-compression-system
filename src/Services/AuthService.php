<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\User;
use App\Interfaces\UserRepositoryInterface;
use RuntimeException;

class AuthService {
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
    }

    public function register(string $username, string $email, string $password): User {
        $username = trim($username);
        $email = trim(strtolower($email));

        if (strlen($username) < 3) {
            throw new RuntimeException("Username must be at least 3 characters.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Please provide a valid email address.");
        }

        if (strlen($password) < 6) {
            throw new RuntimeException("Password must be at least 6 characters long.");
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new RuntimeException("An account with this email already exists.");
        }

        if ($this->userRepository->findByUsername($username) !== null) {
            throw new RuntimeException("Username is already taken. Please choose another.");
        }

        // Secure password hashing with standard bcrypt cost factor 12
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $user = new User(null, $username, $email, $passwordHash);
        $userId = $this->userRepository->createUser($user);
        $user->id = $userId;

        // Auto login after registration
        $this->setUserSession($user);

        return $user;
    }

    public function login(string $identifier, string $password): User {
        $identifier = trim($identifier);

        if (empty($identifier) || empty($password)) {
            throw new RuntimeException("Please enter both username/email and password.");
        }

        // Can login with either email or username
        $user = str_contains($identifier, '@') 
            ? $this->userRepository->findByEmail(strtolower($identifier))
            : $this->userRepository->findByUsername($identifier);

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new RuntimeException("Invalid username/email or password.");
        }

        $this->setUserSession($user);

        return $user;
    }

    public function getCurrentUser(): ?User {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById((int)$userId);
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    private function setUserSession(User $user): void {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['email'] = $user->email;
    }
}
