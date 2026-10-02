<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\EncryptionInterface;
use RuntimeException;

/**
 * Military-grade AES-256-CBC with PBKDF2 Key Derivation and Encrypt-then-MAC (HMAC-SHA256).
 */
class AesEncryptionService implements EncryptionInterface {
    private const CIPHER = 'AES-256-CBC';
    private const MAGIC_HEADER = 'ENC1';
    private const PBKDF2_ITERATIONS = 15000;

    public function getAlgorithmName(): string {
        return 'AES-256-CBC (PBKDF2-HMAC)';
    }

    public function encrypt(string $data, string $password): string {
        if (trim($password) === '') {
            throw new RuntimeException("Encryption password cannot be empty.");
        }

        // 1. Generate cryptographically secure random salt and IV
        $salt = random_bytes(16);
        $iv = random_bytes(16);

        // 2. Derive 64 bytes via PBKDF2 (32 bytes AES key + 32 bytes HMAC key)
        $derived = hash_pbkdf2('sha256', $password, $salt, self::PBKDF2_ITERATIONS, 64, true);
        $encKey = substr($derived, 0, 32);
        $hmacKey = substr($derived, 32, 32);

        // 3. Encrypt data
        $ciphertext = openssl_encrypt($data, self::CIPHER, $encKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new RuntimeException("OpenSSL AES-256 encryption failed: " . openssl_error_string());
        }

        // 4. Calculate HMAC-SHA256 (Encrypt-then-MAC)
        $hmacPayload = $salt . $iv . $ciphertext;
        $hmac = hash_hmac('sha256', $hmacPayload, $hmacKey, true); // 32 bytes

        // 5. Pack container: [ENC1 (4B)][SALT (16B)][IV (16B)][HMAC (32B)][CIPHERTEXT]
        return self::MAGIC_HEADER . $salt . $iv . $hmac . $ciphertext;
    }

    public function decrypt(string $encryptedData, string $password): string {
        if (trim($password) === '') {
            throw new RuntimeException("Password is required to decrypt this file.");
        }

        $minLen = 4 + 16 + 16 + 32;
        if (strlen($encryptedData) < $minLen) {
            throw new RuntimeException("Invalid encrypted file: Payload is truncated.");
        }

        // 1. Verify Magic Header
        $magic = substr($encryptedData, 0, 4);
        if ($magic !== self::MAGIC_HEADER) {
            throw new RuntimeException("Invalid file: Missing encryption signature.");
        }

        // 2. Parse container fields
        $salt = substr($encryptedData, 4, 16);
        $iv = substr($encryptedData, 20, 16);
        $expectedHmac = substr($encryptedData, 36, 32);
        $ciphertext = substr($encryptedData, 68);

        // 3. Derive keys
        $derived = hash_pbkdf2('sha256', $password, $salt, self::PBKDF2_ITERATIONS, 64, true);
        $encKey = substr($derived, 0, 32);
        $hmacKey = substr($derived, 32, 32);

        // 4. Verify HMAC using timing-safe comparison
        $calculatedHmac = hash_hmac('sha256', $salt . $iv . $ciphertext, $hmacKey, true);
        if (!hash_equals($expectedHmac, $calculatedHmac)) {
            throw new RuntimeException("Incorrect password or file has been tampered with.");
        }

        // 5. Decrypt
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $encKey, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new RuntimeException("AES-256 decryption failed: " . openssl_error_string());
        }

        return $plaintext;
    }
}
