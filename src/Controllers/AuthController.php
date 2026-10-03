<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Interfaces\FileRepositoryInterface;
use App\Domain\ActivityLog;

class AuthController {
    private AuthService $authService;
    private FileRepositoryInterface $repository;

    public function __construct(AuthService $authService, FileRepositoryInterface $repository) {
        $this->authService = $authService;
        $this->repository = $repository;
    }

    public function handleRegister(array $input): array {
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $user = $this->authService->register($username, $email, $password);

        $this->repository->logActivity(new ActivityLog(
            null,
            'REGISTER',
            null,
            $user->username,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "New user registered: {$user->email}"
        ));

        return [
            'success' => true,
            'user' => $user->toArray(),
            'message' => "Welcome to SecureCompress, {$user->username}!"
        ];
    }

    public function handleLogin(array $input): array {
        $identifier = $input['identifier'] ?? '';
        $password = $input['password'] ?? '';

        $user = $this->authService->login($identifier, $password);

        $this->repository->logActivity(new ActivityLog(
            null,
            'LOGIN',
            null,
            $user->username,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "User logged in: {$user->email}"
        ));

        return [
            'success' => true,
            'user' => $user->toArray(),
            'message' => "Logged in as {$user->username}."
        ];
    }

    public function handleLogout(): array {
        $user = $this->authService->getCurrentUser();
        $username = $user ? $user->username : 'Guest';

        $this->authService->logout();

        $this->repository->logActivity(new ActivityLog(
            null,
            'LOGOUT',
            null,
            $username,
            'SUCCESS',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "User logged out"
        ));

        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    public function handleMe(): array {
        $user = $this->authService->getCurrentUser();
        return [
            'success' => true,
            'authenticated' => $user !== null,
            'user' => $user ? $user->toArray() : null
        ];
    }
}
