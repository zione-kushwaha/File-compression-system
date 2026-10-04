<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

use App\Services\HuffmanEngine;
use App\Config\Database;

echo "====================================================\n";
echo "   FILE COMPRESSION SYSTEM (HUFFMAN) - TEST SUITE   \n";
echo "====================================================\n\n";

// 1. Database Connection Test
echo "[1] Testing Database Connection...\n";
$db = Database::getConnection();
$driver = Database::getDriverName();
echo "    -> Connected successfully using driver: [{$driver}]\n\n";

// 2. Huffman Compression & Lossless Decompression Test
echo "[2] Testing Huffman Coding Engine...\n";
$huffman = new HuffmanEngine();
$sampleText = "The quick brown fox jumps over the lazy dog. Huffman coding is an entropy encoding algorithm used for lossless data compression. AABBCCCCCCDDDDDDDD";
$compResult = $huffman->compress($sampleText);

echo "    -> Original Size: " . strlen($sampleText) . " bytes\n";
echo "    -> Compressed Size: " . $compResult['stats']['compressedSize'] . " bytes\n";
echo "    -> Shannon Entropy: " . $compResult['stats']['entropy'] . " bits/symbol\n";
echo "    -> Avg Code Length: " . $compResult['stats']['avgCodeLength'] . " bits/symbol\n";

$restoredText = $huffman->decompress($compResult['compressedData']);
if ($restoredText === $sampleText) {
    echo "    -> Huffman Lossless Check: PASSED (Exact byte-for-byte match!)\n\n";
} else {
    echo "    -> Huffman Lossless Check: FAILED!\n\n";
    exit(1);
}

// 3. Large File Compression Benchmark
echo "[3] Testing Large File Huffman Compression...\n";
$largeText = str_repeat("Lossless Huffman coding replaces high-frequency bytes with short binary prefix codes. ", 500);
$origSize = strlen($largeText);
$largeResult = $huffman->compress($largeText);
$compSize = strlen($largeResult['compressedData']);
$ratio = round((1 - ($compSize / $origSize)) * 100, 2);

echo "    -> Original Text Size: {$origSize} bytes\n";
echo "    -> Compressed Size: {$compSize} bytes\n";
echo "    -> Space Saved: +{$ratio}%\n";

$largeRestored = $huffman->decompress($largeResult['compressedData']);
if ($largeRestored === $largeText && hash('sha256', $largeRestored) === hash('sha256', $largeText)) {
    echo "    -> SHA-256 Checksum Verification: PASSED (Hashes Match!)\n\n";
} else {
    echo "    -> Checksum Verification: FAILED!\n\n";
    exit(1);
}

// 4. User Authentication & Password Hashing Test
echo "[4] Testing User Registration & Password Hashing...\n";
$userRepo = new \App\Repositories\DatabaseUserRepository();
$auth = new \App\Services\AuthService($userRepo);

$randomId = time();
$testUser = "test_user_{$randomId}";
$testEmail = "student_{$randomId}@university.edu";
$registeredUser = $auth->register($testUser, $testEmail, "SecurePassword123!");
echo "    -> Registered User: {$registeredUser->username} ({$registeredUser->email})\n";
echo "    -> Password Hash: " . substr($registeredUser->passwordHash, 0, 25) . "...\n";

// Verify bcrypt hash structure ($2y$...)
if (str_starts_with($registeredUser->passwordHash, '$2y$')) {
    echo "    -> Bcrypt Hashing: PASSED\n";
} else {
    echo "    -> Bcrypt Hashing: FAILED\n";
    exit(1);
}

// Test Login with correct credentials
$loggedUser = $auth->login($testUser, "SecurePassword123!");
if ($loggedUser !== null && $loggedUser->username === $testUser) {
    echo "    -> Valid Credential Login: PASSED\n";
} else {
    echo "    -> Login: FAILED\n";
    exit(1);
}

// Test Login with wrong password
try {
    $auth->login($testUser, "WrongPassword999!");
    echo "    -> Wrong Password Check: FAILED (Should have thrown exception)\n\n";
    exit(1);
} catch (RuntimeException $e) {
    echo "    -> Wrong Password Rejection: PASSED (" . $e->getMessage() . ")\n\n";
}

echo "====================================================\n";
echo "   ALL HUFFMAN SYSTEM TESTS PASSED SUCCESSFULLY!    \n";
echo "====================================================\n";
