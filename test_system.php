<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

use App\Services\HuffmanEngine;
use App\Services\ZipEngine;
use App\Services\AesEncryptionService;
use App\Config\Database;

echo "====================================================\n";
echo "   SECURE FILE COMPRESSION SYSTEM - TEST SUITE      \n";
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
echo "    -> Compression Ratio: " . $compResult['stats']['ratio'] . "%\n";
echo "    -> Shannon Entropy: " . $compResult['stats']['entropy'] . " bits/symbol\n";
echo "    -> Avg Code Length: " . $compResult['stats']['avgCodeLength'] . " bits/symbol\n";

$restoredText = $huffman->decompress($compResult['compressedData']);
if ($restoredText === $sampleText) {
    echo "    -> Huffman Lossless Check: PASSED (Exact byte-for-byte match!)\n\n";
} else {
    echo "    -> Huffman Lossless Check: FAILED!\n\n";
    exit(1);
}

// 3. AES-256 Authenticated Encryption Test
echo "[3] Testing AES-256 Authenticated Encryption...\n";
$aes = new AesEncryptionService();
$password = "SecretAcademicKey!2026";
$encrypted = $aes->encrypt($compResult['compressedData'], $password);
echo "    -> Encrypted payload size: " . strlen($encrypted) . " bytes\n";

$decrypted = $aes->decrypt($encrypted, $password);
if ($decrypted === $compResult['compressedData']) {
    echo "    -> Correct Password Decryption: PASSED\n";
} else {
    echo "    -> Decryption: FAILED\n";
    exit(1);
}

// Test Wrong Password
try {
    $aes->decrypt($encrypted, "WrongPassword");
    echo "    -> Tamper/Wrong Password Check: FAILED (Should have thrown exception)\n\n";
    exit(1);
} catch (RuntimeException $e) {
    echo "    -> Wrong Password Rejection: PASSED (" . $e->getMessage() . ")\n\n";
}

// 4. End-to-End Pipeline (Original -> Huffman -> AES-256 -> Decrypt -> Decompress -> Original)
echo "[4] Testing Full Roundtrip Pipeline...\n";
$pipelineRestored = $huffman->decompress($aes->decrypt($encrypted, $password));
if ($pipelineRestored === $sampleText) {
    echo "    -> Full Secure Pipeline (Huffman + AES-256): 100% VERIFIED SUCCESS!\n\n";
} else {
    echo "    -> Full Pipeline: FAILED\n\n";
    exit(1);
}

// 5. ZIP Engine Test
echo "[5] Testing ZIP Engine...\n";
$zip = new ZipEngine();
$zipResult = $zip->compress($sampleText);
$zipRestored = $zip->decompress($zipResult['compressedData']);
if ($zipRestored === $sampleText) {
    echo "    -> ZIP Deflate Check: PASSED\n\n";
} else {
    echo "    -> ZIP Check: FAILED\n\n";
    exit(1);
}

// 6. User Authentication & Password Hashing Test
echo "[6] Testing User Registration & Password Hashing...\n";
$userRepo = new \App\Repositories\DatabaseUserRepository();
$auth = new \App\Services\AuthService($userRepo);

$testEmail = "student_" . time() . "@university.edu";
$registeredUser = $auth->register("test_student", $testEmail, "SecurePassword123!");
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
$loggedInUser = $auth->login($testEmail, "SecurePassword123!");
if ($loggedInUser->id === $registeredUser->id) {
    echo "    -> Valid Credential Login: PASSED\n";
} else {
    echo "    -> Valid Credential Login: FAILED\n";
    exit(1);
}

// Test Login with wrong credentials
try {
    $auth->login($testEmail, "WrongPassword!");
    echo "    -> Wrong Password Login: FAILED (Should reject)\n";
    exit(1);
} catch (RuntimeException $e) {
    echo "    -> Wrong Password Rejection: PASSED (" . $e->getMessage() . ")\n\n";
}

echo "====================================================\n";
echo "   ALL TESTS PASSED WITH ZERO ERRORS!               \n";
echo "====================================================\n";
