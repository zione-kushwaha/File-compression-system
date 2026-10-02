<?php
declare(strict_types=1);

// Application Constants & Paths
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('COMPRESSED_PATH', STORAGE_PATH . '/compressed');
define('DECOMPRESSED_PATH', STORAGE_PATH . '/decompressed');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB

// PSR-4 style autoloader for App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/src/';

    if ($class === 'App\\Config\\Database') {
        require_once APP_ROOT . '/config/database.php';
        return;
    }

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
