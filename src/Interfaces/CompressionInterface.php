<?php
declare(strict_types=1);

namespace App\Interfaces;

interface CompressionInterface {
    /**
     * Compresses the raw binary data string.
     * 
     * @param string $data Raw file content
     * @return array Contains 'compressedData', 'metadata', 'stats'
     */
    public function compress(string $data): array;

    /**
     * Decompresses the compressed binary string back to original binary data.
     * 
     * @param string $compressedData
     * @return string Original raw file content
     */
    public function decompress(string $compressedData): string;

    /**
     * Get algorithm identifier name (e.g. 'huffman', 'zip').
     */
    public function getName(): string;
}
