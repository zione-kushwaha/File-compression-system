<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Domain Node for Huffman Coding Tree.
 * Represents either a leaf node (with a specific byte/character) or an internal node.
 */
class HuffmanNode {
    public ?int $byte;             // 0-255 byte value (null for internal nodes)
    public int $frequency;         // Occurrence count of the byte/subtree
    public ?HuffmanNode $left;     // Left child (represents bit '0')
    public ?HuffmanNode $right;    // Right child (represents bit '1')

    public function __construct(?int $byte, int $frequency, ?HuffmanNode $left = null, ?HuffmanNode $right = null) {
        $this->byte = $byte;
        $this->frequency = $frequency;
        $this->left = $left;
        $this->right = $right;
    }

    public function isLeaf(): bool {
        return $this->left === null && $this->right === null;
    }

    /**
     * Converts the tree into a nested array structure suitable for JSON export / SVG visualization.
     */
    public function toArray(): array {
        $data = [
            'frequency' => $this->frequency,
            'isLeaf' => $this->isLeaf(),
        ];

        if ($this->isLeaf()) {
            $char = $this->byte !== null ? chr($this->byte) : '';
            $displayChar = ctype_print($char) && !ctype_space($char) ? $char : '0x' . strtoupper(dechex($this->byte ?? 0));
            if ($this->byte === 32) $displayChar = 'SPACE';
            if ($this->byte === 10) $displayChar = '\\n';
            if ($this->byte === 13) $displayChar = '\\r';
            if ($this->byte === 9)  $displayChar = '\\t';

            $data['byte'] = $this->byte;
            $data['char'] = $displayChar;
        } else {
            $data['left'] = $this->left?->toArray();
            $data['right'] = $this->right?->toArray();
        }

        return $data;
    }
}
