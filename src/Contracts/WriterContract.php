<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Contracts;

use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\ValueObjects\UInt64;

/**
 * Defines operations for writing typed values to binary data.
 */
interface WriterContract
{
    /**
     * Write raw bytes at the current stream position.
     *
     * @param string $data Binary data to write
     * @return int Number of bytes written
     */
    public function writeBytes(string $data): int;

    /**
     * Write a signed 8-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 8-bit range
     */
    public function writeInt8(int $value): int;

    /**
     * Write a signed 16-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 16-bit range
     */
    public function writeInt16(int $value): int;

    /**
     * Write a signed 32-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 32-bit range
     */
    public function writeInt32(int $value): int;

    /**
     * Write a signed 64-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     */
    public function writeInt64(int $value): int;

    /**
     * Write an unsigned 8-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 8-bit range
     */
    public function writeUInt8(int $value): int;

    /**
     * Write an unsigned 16-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 16-bit range
     */
    public function writeUInt16(int $value): int;

    /**
     * Write an unsigned 32-bit integer.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 32-bit range
     */
    public function writeUInt32(int $value): int;

    /**
     * Write an unsigned 64-bit integer.
     *
     * @param UInt64 $value Value to write
     * @return int Number of bytes written
     */
    public function writeUInt64(UInt64 $value): int;

    /**
     * Write a single-precision floating-point value.
     *
     * @param float $value Value to write
     * @return int Number of bytes written
     */
    public function writeFloat(float $value): int;

    /**
     * Write a double-precision floating-point value.
     *
     * @param float $value Value to write
     * @return int Number of bytes written
     */
    public function writeDouble(float $value): int;

    /**
     * Write a null-terminated string.
     *
     * @param string $value String to write
     * @return int Number of bytes written
     * @throws InvalidArgumentException When the string contains a null byte
     */
    public function writeStringWithNullTerminator(string $value): int;

    /**
     * Write a string preceded by an unsigned length prefix.
     *
     * @param string $value String to write
     * @param LengthPrefixSize $lengthPrefixSize Width of the length prefix
     * @return int Number of string bytes written
     * @throws OverflowException When the string length does not fit in the prefix
     */
    public function writeStringWithLengthPrefix(
        string $value,
        LengthPrefixSize $lengthPrefixSize = LengthPrefixSize::INT
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
