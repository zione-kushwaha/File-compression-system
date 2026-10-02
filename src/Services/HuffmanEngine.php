<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\HuffmanNode;
use App\Interfaces\CompressionInterface;
use SplPriorityQueue;
use RuntimeException;

/**
 * Min-Priority Queue implementation for Huffman Tree construction.
 */
class HuffmanPriorityQueue extends SplPriorityQueue {
    public function compare(mixed $priority1, mixed $priority2): int {
        // SplPriorityQueue is max-heap by default; invert comparison for min-heap
        return $priority2 <=> $priority1;
    }
}

class HuffmanEngine implements CompressionInterface {
    private const MAGIC_HEADER = 'HUF1'; // 4 bytes

    public function getName(): string {
        return 'huffman';
    }

    /**
     * Compresses raw binary data using Huffman Variable-Length Coding.
     */
    public function compress(string $data): array {
        $originalSize = strlen($data);
        if ($originalSize === 0) {
            throw new RuntimeException("Cannot compress empty file data.");
        }

        // 1. Build byte frequency map
        $frequencies = [];
        for ($i = 0; $i < $originalSize; $i++) {
            $byte = ord($data[$i]);
            $frequencies[$byte] = ($frequencies[$byte] ?? 0) + 1;
        }

        // 2. Build Huffman Tree using Priority Queue
        $root = $this->buildTree($frequencies);

        // 3. Generate bit-codebook from tree
        $codebook = [];
        $this->generateCodes($root, '', $codebook);

        // In edge case of single distinct character
        if (count($frequencies) === 1) {
            $singleByte = array_key_first($frequencies);
            $codebook[$singleByte] = '0';
        }

        // 4. Calculate Shannon Entropy & Theoretical Metrics
        $entropy = 0.0;
        $totalBits = 0;
        foreach ($frequencies as $byte => $freq) {
            $p = $freq / $originalSize;
            $entropy -= $p * (log($p) / log(2));
            $totalBits += $freq * strlen($codebook[$byte]);
        }
        $avgCodeLength = $totalBits / $originalSize;

        // 5. Pack data into binary format:
        // [MAGIC 4B][ORIG_SIZE 4B][DISTINCT_COUNT 2B][FREQ_TABLE (1B byte + 4B freq)...][BITSTREAM...]
        $header = self::MAGIC_HEADER;
        $header .= pack('N', $originalSize); // 4-byte unsigned int
        $distinctCount = count($frequencies);
        $header .= pack('n', $distinctCount); // 2-byte unsigned short

        foreach ($frequencies as $byte => $freq) {
            $header .= chr($byte) . pack('N', $freq);
        }

        // 6. Encode bitstream
        $bitBuffer = 0;
        $bitsInBuffer = 0;
        $encodedBytes = '';

        for ($i = 0; $i < $originalSize; $i++) {
            $byte = ord($data[$i]);
            $code = $codebook[$byte];
            $codeLen = strlen($code);

            for ($j = 0; $j < $codeLen; $j++) {
                $bit = $code[$j] === '1' ? 1 : 0;
                $bitBuffer = ($bitBuffer << 1) | $bit;
                $bitsInBuffer++;

                if ($bitsInBuffer === 8) {
                    $encodedBytes .= chr($bitBuffer);
                    $bitBuffer = 0;
                    $bitsInBuffer = 0;
                }
            }
        }

        // Flush remaining bits in buffer (padded with trailing zeros)
        if ($bitsInBuffer > 0) {
            $bitBuffer = $bitBuffer << (8 - $bitsInBuffer);
            $encodedBytes .= chr($bitBuffer);
        }

        $compressedData = $header . $encodedBytes;
        $compressedSize = strlen($compressedData);
        $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 2);

        // Format codebook for UI
        $uiCodebook = [];
        foreach ($frequencies as $byte => $freq) {
            $char = chr($byte);
            $displayChar = ctype_print($char) && !ctype_space($char) ? $char : '0x' . strtoupper(str_pad(dechex($byte), 2, '0', STR_PAD_LEFT));
            if ($byte === 32) $displayChar = '[SPACE]';
            if ($byte === 10) $displayChar = '\\n (LF)';
            if ($byte === 13) $displayChar = '\\r (CR)';
            if ($byte === 9)  $displayChar = '\\t (TAB)';

            $code = $codebook[$byte];
            $uiCodebook[] = [
                'byte' => $byte,
                'char' => $displayChar,
                'frequency' => $freq,
                'probability' => round(($freq / $originalSize) * 100, 2),
                'code' => $code,
                'length' => strlen($code)
            ];
        }

        // Sort by frequency descending
        usort($uiCodebook, fn($a, $b) => $b['frequency'] <=> $a['frequency']);

        return [
            'compressedData' => $compressedData,
            'stats' => [
                'originalSize' => $originalSize,
                'compressedSize' => $compressedSize,
                'ratio' => $compressionRatio,
                'entropy' => round($entropy, 4),
                'avgCodeLength' => round($avgCodeLength, 4),
                'distinctBytes' => $distinctCount,
                'efficiency' => $avgCodeLength > 0 ? round(($entropy / $avgCodeLength) * 100, 2) : 100.0,
            ],
            'codebook' => $uiCodebook,
            'tree' => $root->toArray(),
        ];
    }

    /**
     * Decompresses Huffman binary stream back to original data.
     */
    public function decompress(string $compressedData): string {
        $len = strlen($compressedData);
        if ($len < 10) {
            throw new RuntimeException("Invalid Huffman file: Header too short.");
        }

        // Verify magic header
        $magic = substr($compressedData, 0, 4);
        if ($magic !== self::MAGIC_HEADER) {
            throw new RuntimeException("Invalid file format: Magic header mismatch.");
        }

        $offset = 4;
        $origSizeData = unpack('Nsize', substr($compressedData, $offset, 4));
        $originalSize = $origSizeData['size'];
        $offset += 4;

        $distinctData = unpack('ncount', substr($compressedData, $offset, 2));
        $distinctCount = $distinctData['count'];
        $offset += 2;

        // Reconstruct frequency table
        $frequencies = [];
        for ($k = 0; $k < $distinctCount; $k++) {
            if ($offset + 5 > $len) {
                throw new RuntimeException("Corrupted Huffman file header.");
            }
            $byte = ord($compressedData[$offset]);
            $offset += 1;
            $freqData = unpack('Nfreq', substr($compressedData, $offset, 4));
            $frequencies[$byte] = $freqData['freq'];
            $offset += 4;
        }

        // Rebuild Huffman Tree
        $root = $this->buildTree($frequencies);

        // Edge case: single distinct character
        if (count($frequencies) === 1) {
            $singleByte = array_key_first($frequencies);
            return str_repeat(chr($singleByte), $originalSize);
        }

        // Traverse bitstream against tree
        $decoded = '';
        $decodedCount = 0;
        $currentNode = $root;

        for ($i = $offset; $i < $len; $i++) {
            $byteVal = ord($compressedData[$i]);

            for ($bit = 7; $bit >= 0; $bit--) {
                $bitVal = ($byteVal >> $bit) & 1;

                $currentNode = ($bitVal === 0) ? $currentNode->left : $currentNode->right;

                if ($currentNode === null) {
                    throw new RuntimeException("Corrupted bitstream encountered during decompression.");
                }

                if ($currentNode->isLeaf()) {
                    $decoded .= chr($currentNode->byte);
                    $decodedCount++;
                    $currentNode = $root;

                    if ($decodedCount === $originalSize) {
                        break 2; // Decoded all original bytes
                    }
                }
            }
        }

        if ($decodedCount !== $originalSize) {
            throw new RuntimeException("Decompression error: Expected {$originalSize} bytes, got {$decodedCount} bytes.");
        }

        return $decoded;
    }

    private function buildTree(array $frequencies): HuffmanNode {
        $pq = new HuffmanPriorityQueue();

        foreach ($frequencies as $byte => $freq) {
            $node = new HuffmanNode($byte, $freq);
            $pq->insert($node, $freq);
        }

        if ($pq->count() === 1) {
            $onlyNode = $pq->extract();
            return new HuffmanNode(null, $onlyNode->frequency, $onlyNode, null);
        }

        while ($pq->count() > 1) {
            /** @var HuffmanNode $left */
            $left = $pq->extract();
            /** @var HuffmanNode $right */
            $right = $pq->extract();

            $parent = new HuffmanNode(
                null,
                $left->frequency + $right->frequency,
                $left,
                $right
            );
            $pq->insert($parent, $parent->frequency);
        }

        return $pq->extract();
    }

    private function generateCodes(?HuffmanNode $node, string $code, array &$codebook): void {
        if ($node === null) {
            return;
        }

        if ($node->isLeaf() && $node->byte !== null) {
            $codebook[$node->byte] = ($code === '') ? '0' : $code;
            return;
        }

        $this->generateCodes($node->left, $code . '0', $codebook);
        $this->generateCodes($node->right, $code . '1', $codebook);
    }
}
