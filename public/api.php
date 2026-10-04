<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

use App\Repositories\DatabaseFileRepository;
use App\Repositories\DatabaseUserRepository;
use App\Services\HuffmanEngine;
use App\Services\FileStorageService;
use App\Services\AuthService;
use App\Controllers\CompressionController;
use App\Controllers\FileController;
use App\Controllers\AuthController;

// Dependency Injection Setup (Clean Architecture composition root)
$repository = new DatabaseFileRepository();
$userRepository = new DatabaseUserRepository();
$authService = new AuthService($userRepository);

$huffmanEngine = new HuffmanEngine();
$storageService = new FileStorageService();

$compressionController = new CompressionController(
    $repository,
    $huffmanEngine,
    $storageService,
    $authService
);

$fileController = new FileController($repository, $storageService);
$authController = new AuthController($authService, $repository);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$currentUser = $authService->getCurrentUser();

// Handle binary download actions directly without JSON headers
if ($action === 'download') {
    if (!$currentUser) {
        http_response_code(401);
        die("Access denied. Please login first.");
    }
    $id = (int)($_GET['id'] ?? 0);
    $fileController->downloadFile($id);
    exit;
}

if ($action === 'download_decompressed') {
    if (!$currentUser) {
        http_response_code(401);
        die("Access denied. Please login first.");
    }
    $filename = $_GET['file'] ?? '';
    $name = $_GET['name'] ?? 'decompressed_file';
    $fileController->downloadDecompressed($filename, $name);
    exit;
}

// All other requests return JSON
header('Content-Type: application/json; charset=utf-8');

// Enforce authentication for protected actions
$publicActions = ['login', 'register', 'me', 'system_info'];
if (!$currentUser && !in_array($action, $publicActions, true)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'requireAuth' => true,
        'error' => 'Authentication required. Please sign in to access the system.'
    ]);
    exit;
}

try {
    switch ($action) {
        case 'login':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            echo json_encode($authController->handleLogin($input));
            break;

        case 'register':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            echo json_encode($authController->handleRegister($input));
            break;

        case 'logout':
            echo json_encode($authController->handleLogout());
            break;

        case 'me':
            echo json_encode($authController->handleMe());
            break;

        case 'compress':
            $fileUpload = $_FILES['file'] ?? [];
            $response = $compressionController->handleCompress($fileUpload);
            echo json_encode($response);
            break;

        case 'decompress':
            $fileUpload = $_FILES['file'] ?? null;
            $fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : null;
            $response = $compressionController->handleDecompress($fileUpload, $fileId);
            echo json_encode($response);
            break;

        case 'list':
            echo json_encode($fileController->listFiles());
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            echo json_encode($fileController->deleteFile($id));
            break;

        case 'logs':
            echo json_encode($fileController->getLogs());
            break;

        case 'system_info':
            echo json_encode($fileController->getSystemInfo());
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
