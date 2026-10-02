<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\CompressionInterface;
use ZipArchive;
use RuntimeException;

class ZipEngine implements CompressionInterface {
    public function getName(): string {
        return 'zip';
    }

    public function compress(string $data): array {
        $originalSize = strlen($data);
        if ($originalSize === 0) {
            throw new RuntimeException("Cannot compress empty file data.");
        }

        // We use gzcompress / Deflate with maximum compression (level 9)
        $compressedBytes = gzcompress($data, 9);
        if ($compressedBytes === false) {
            throw new RuntimeException("Gzcompress failed on input data.");
        }

        $header = "ZIP1" . pack('N', $originalSize);
        $compressedData = $header . $compressedBytes;
        $compressedSize = strlen($compressedData);
        $ratio = round((1 - ($compressedSize / $originalSize)) * 100, 2);

        return [
            'compressedData' => $compressedData,
            'stats' => [
                'originalSize' => $originalSize,
                'compressedSize' => $compressedSize,
                'ratio' => $ratio,
                'algorithm' => 'Deflate (RFC 1951) Level 9'
            ],
            'codebook' => [],
            'tree' => null,
        ];
    }

    public function decompress(string $compressedData): string {
        $len = strlen($compressedData);
        if ($len < 8) {
            throw new RuntimeException("Invalid Zip data: header too short.");
        }

        $magic = substr($compressedData, 0, 4);
        if ($magic !== 'ZIP1') {
            throw new RuntimeException("Invalid Zip data: magic header mismatch.");
        }

        $unpacked = unpack('Nsize', substr($compressedData, 4, 4));
        $originalSize = $unpacked['size'];

        $payload = substr($compressedData, 8);
        $decompressed = gzuncompress($payload);
        if ($decompressed === false) {
            throw new RuntimeException("Decompression failed: corrupt Deflate payload.");
        }

        return $decompressed;
    }
}
