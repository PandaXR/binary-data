<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Contracts;

use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\ValueObjects\UInt64;

/**
 * Defines operations for writing typed values to binary data.
 *
 * Implementations must either write each complete encoded value or throw an exception.
 * Fixed-size and raw-byte writes return no redundant byte count. Floating-point and
 * encoded-string writes return their total runtime-encoded size.
 */
interface WriterContract
{
    /**
     * Write raw bytes at the current stream position.
     *
     * @param string $data Binary data to write
     * @return void
     */
    public function writeBytes(string $data): void;

    /**
     * Write a signed 8-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 8-bit range
     */
    public function writeInt8(int $value): void;

    /**
     * Write a signed 16-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 16-bit range
     */
    public function writeInt16(int $value): void;

    /**
     * Write a signed 32-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 32-bit range
     */
    public function writeInt32(int $value): void;

    /**
     * Write a signed 64-bit integer.
     *
     * @param int $value Value to write
     * @return void
     */
    public function writeInt64(int $value): void;

    /**
     * Write an unsigned 8-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 8-bit range
     */
    public function writeUInt8(int $value): void;

    /**
     * Write an unsigned 16-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 16-bit range
     */
    public function writeUInt16(int $value): void;

    /**
     * Write an unsigned 32-bit integer.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 32-bit range
     */
    public function writeUInt32(int $value): void;

    /**
     * Write an unsigned 64-bit integer.
     *
     * @param UInt64 $value Value to write
     * @return void
     */
    public function writeUInt64(UInt64 $value): void;

    /**
     * Write a single-precision floating-point value.
     *
     * @param float $value Value to write
     * @return int Number of encoded bytes written
     */
    public function writeFloat(float $value): int;

    /**
     * Write a double-precision floating-point value.
     *
     * @param float $value Value to write
     * @return int Number of encoded bytes written
     */
    public function writeDouble(float $value): int;

    /**
     * Write a null-terminated string.
     *
     * @param string $value String to write
     * @return int Number of encoded bytes written, including the null terminator
     * @throws InvalidArgumentException When the string contains a null byte
     */
    public function writeNullTerminatedString(string $value): int;

    /**
     * Write a string preceded by an unsigned length prefix.
     *
     * @param string $value String to write
     * @param LengthPrefixSize $prefixSize Width of the length prefix
     * @return int Number of encoded bytes written, including the length prefix
     * @throws OverflowException When the string length does not fit in the prefix
     */
    public function writeLengthPrefixedString(
        string $value,
        LengthPrefixSize $prefixSize = LengthPrefixSize::UINT32
    ): int;

    /**
     * Get the current stream position.
     *
     * @return int Current zero-based stream position
     */
    public function getPosition(): int;

    /**
     * Move the stream cursor to an absolute byte position.
     *
     * @param int $position Zero-based byte position
     * @return void
     */
    public function setPosition(int $position): void;
}
