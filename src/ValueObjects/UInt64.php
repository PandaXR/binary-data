<?php

declare(strict_types=1);

namespace Lxr\BinaryData\ValueObjects;

use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;

/**
 * Immutable unsigned 64-bit value stored as eight bytes in canonical big-endian order.
 *
 * The byte-backed representation preserves the complete 0 through 2^64-1 range without
 * converting values above PHP_INT_MAX to floating point.
 */
final readonly class UInt64
{
    /**
     * Create an unsigned 64-bit value from canonical big-endian bytes.
     *
     * @param string $bigEndianBytes Exactly eight bytes in big-endian order
     * @throws InvalidArgumentException When the value does not contain exactly eight bytes
     */
    public function __construct(
        private string $bigEndianBytes
    ) {
        if (strlen($bigEndianBytes) !== 8) {
            throw new InvalidArgumentException(
                'UInt64 must contain exactly 8 bytes'
            );
        }
    }

    /**
     * Convert the value to a non-negative PHP integer.
     *
     * @return int Value in the range 0 through PHP_INT_MAX
     * @throws OverflowException When the value is greater than PHP_INT_MAX
     * @throws RuntimeException When the underlying bytes cannot be unpacked
     */
    public function toInt(): int
    {
        // The highest bit means the value exceeds PHP_INT_MAX.
        if ((ord($this->bigEndianBytes[0]) & 0x80) !== 0) {
            throw new OverflowException(
                'UInt64 value cannot be represented as a PHP int'
            );
        }

        $unpackedData = unpack('J', $this->bigEndianBytes);
        if (!$unpackedData) {
            throw new RuntimeException('Failed to unpack UInt64 value');
        }

        return $unpackedData[1];
    }

    /**
     * Convert the complete unsigned value to base-10 notation without precision loss.
     *
     * @return string Unsigned decimal representation
     * @throws RuntimeException When the underlying bytes cannot be unpacked
     */
    public function toDecimalString(): string
    {
        $bits = unpack('J', $this->bigEndianBytes);
        if ($bits === false) {
            throw new RuntimeException('Failed to unpack UInt64 value');
        }

        return sprintf('%u', $bits[1]);
    }

    /**
     * Convert the value to 16 lowercase hexadecimal digits.
     *
     * @return string Hexadecimal representation without a prefix
     */
    public function toHex(): string
    {
        return bin2hex($this->bigEndianBytes);
    }

    /**
     * Get the canonical big-endian representation.
     *
     * @return string Exactly eight bytes in big-endian order
     */
    public function toBigEndianBytes(): string
    {
        return $this->bigEndianBytes;
    }

    /**
     * Get the little-endian representation.
     *
     * @return string Exactly eight bytes in little-endian order
     */
    public function toLittleEndianBytes(): string
    {
        return strrev($this->bigEndianBytes);
    }
}
