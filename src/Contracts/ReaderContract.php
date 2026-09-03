<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Contracts;

use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\ValueObjects\UInt64;

/**
 * Defines operations for reading typed values from binary data.
 */
interface ReaderContract
{
    /**
     * Read raw bytes from the current stream position.
     *
     * @param int $length Number of bytes to read
     * @return string Binary data read from the stream
     */
    public function readBytes(int $length): string;

    /**
     * Read bytes until a delimiter or the end of the stream is encountered.
     *
     * @param string $delimiter Delimiter that terminates the read
     * @return string Bytes preceding the delimiter
     * @throws InvalidArgumentException When the delimiter is empty
     * @throws OverflowException When the configured maximum string length is exceeded
     */
    public function readUntil(string $delimiter): string;

    /**
     * Read an 8-bit signed integer.
     *
     * @return int Signed 8-bit value
     */
    public function readInt8(): int;

    /**
     * Read a 16-bit signed integer.
     *
     * @return int Signed 16-bit value
     */
    public function readInt16(): int;

    /**
     * Read a 32-bit signed integer.
     *
     * @return int Signed 32-bit value
     */
    public function readInt32(): int;

    /**
     * Read a 64-bit signed integer.
     *
     * @return int Signed 64-bit value
     */
    public function readInt64(): int;

    /**
     * Read an 8-bit unsigned integer.
     *
     * @return int Unsigned 8-bit value
     */
    public function readUInt8(): int;

    /**
     * Read a 16-bit unsigned integer.
     *
     * @return int Unsigned 16-bit value
     */
    public function readUInt16(): int;

    /**
     * Read a 32-bit unsigned integer.
     *
     * @return int Unsigned 32-bit value
     */
    public function readUInt32(): int;

    /**
     * Read a 64-bit unsigned integer.
     *
     * @return UInt64 Unsigned 64-bit value
     */
    public function readUInt64(): UInt64;

    /**
     * Read a single-precision floating-point value.
     *
     * @return float Single-precision floating-point value
     */
    public function readFloat(): float;

    /**
     * Read a double-precision floating-point value.
     *
     * @return float Double-precision floating-point value
     */
    public function readDouble(): float;

    /**
     * Read a string preceded by an unsigned length prefix.
     *
     * @param LengthPrefixSize $prefixSize Width of the length prefix
     * @return string String read from the stream
     * @throws OverflowException When the configured maximum string length is exceeded
     * @throws UnexpectedValueException When the stream ends before the string is complete
     */
    public function readLengthPrefixedString(
        LengthPrefixSize $prefixSize = LengthPrefixSize::UINT32
    ): string;

    /**
     * Read a null-terminated string.
     *
     * @return string String preceding the null terminator
     * @throws OverflowException When the configured maximum string length is exceeded
     * @throws UnexpectedValueException When the stream ends before the null terminator
     */
    public function readNullTerminatedString(): string;

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
