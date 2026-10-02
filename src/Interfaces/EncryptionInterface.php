<?php
declare(strict_types=1);

namespace App\Interfaces;

interface EncryptionInterface {
    /**
     * Encrypts plaintext data with AES-256 using user password.
     */
    public function encrypt(string $data, string $password): string;

    /**
     * Decrypts encrypted data with AES-256 using user password.
     * Throws exception if password is wrong or ciphertext is tampered.
     */
    public function decrypt(string $encryptedData, string $password): string;

    /**
     * Get encryption algorithm name (e.g. 'AES-256-CBC' or 'AES-256-GCM').
     */
    public function getAlgorithmName(): string;
}
