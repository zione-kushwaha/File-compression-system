<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'secure_compress';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        // Attempt MySQL connection first
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);

            // Ensure database exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            self::initializeTables($pdo, 'mysql');
            self::$connection = $pdo;
            self::$driver = 'mysql';
            return self::$connection;
        } catch (PDOException $e) {
            // MySQL unavailable: seamlessly fall back to SQLite in storage/
            $sqlitePath = dirname(__DIR__) . '/storage/database.sqlite';
            $dsn = "sqlite:" . $sqlitePath;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            self::initializeTables($pdo, 'sqlite');
            self::$connection = $pdo;
            self::$driver = 'sqlite';
            return self::$connection;
        }
    }

    public static function getDriverName(): string {
        if (self::$connection === null) {
            self::getConnection();
        }
        return self::$driver;
    }

    private static function initializeTables(PDO $pdo, string $driver): void {
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                original_name TEXT NOT NULL,
                stored_name TEXT NOT NULL,
                mime_type TEXT DEFAULT 'application/octet-stream',
                algorithm TEXT NOT NULL,
                is_encrypted INTEGER DEFAULT 0,
                encryption_algorithm TEXT NULL,
                original_size INTEGER NOT NULL,
                compressed_size INTEGER NOT NULL,
                compression_ratio REAL NOT NULL,
                sha256_checksum TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                file_id INTEGER NULL,
                filename TEXT NOT NULL,
                status TEXT NOT NULL,
                ip_address TEXT NULL,
                details TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Seed demo_user with Admin@123
            $pdo->exec("INSERT OR REPLACE INTO users (id, username, email, password_hash)
                        VALUES (1, 'demo_user', 'demo@compression.local', '$2y$12\$IdKYqBtAYsz3092qymzCt.sH1A/VmPHFEHLYnzq7Uvttd89USJ9cS')");
        } else {
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        try {
                            $pdo->exec($query);
                        } catch (PDOException $ignore) {}
                    }
                }
            }
        }
    }
}
