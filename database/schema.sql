-- Secure File Compression System Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB and SQLite

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) DEFAULT 'application/octet-stream',
    algorithm VARCHAR(50) NOT NULL,            -- 'huffman' or 'zip'
    is_encrypted TINYINT(1) DEFAULT 0,         -- 1 = Yes, 0 = No
    encryption_algorithm VARCHAR(50) NULL,     -- 'AES-256-CBC'
    original_size BIGINT NOT NULL,             -- Bytes
    compressed_size BIGINT NOT NULL,           -- Bytes
    compression_ratio DECIMAL(6, 2) NOT NULL,  -- e.g. 42.50 (%)
    sha256_checksum VARCHAR(64) NOT NULL,      -- Hash of original file
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,               -- 'COMPRESS', 'DECOMPRESS', 'DOWNLOAD', 'DELETE'
    file_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,               -- 'SUCCESS', 'FAILED'
    ip_address VARCHAR(45) NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default demo user (Username: demo_user, Password: Admin@123)
-- Password hash generated with BCRYPT cost 12
INSERT INTO users (id, username, email, password_hash) 
VALUES (1, 'demo_user', 'demo@compression.local', '$2y$12$IdKYqBtAYsz3092qymzCt.sH1A/VmPHFEHLYnzq7Uvttd89USJ9cS')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
