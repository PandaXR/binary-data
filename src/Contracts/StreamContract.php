<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Contracts;

use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\RuntimeException;

/**
 * Defines the stream operations required by binary-data readers and writers.
 *
 * Implementations are responsible for maintaining a byte position and for reporting
 * failures through exceptions rather than ambiguous return values.
 */
interface StreamContract
{
    /**
     * Get the current zero-based byte position.
     *
     * @return int Current position in bytes
     * @throws RuntimeException When the stream is closed or its position cannot be read
     */
    public function getPosition(): int;

    /**
     * Move the stream cursor to an absolute byte position.
     *
     * @param int $position Zero-based byte position
     * @return void
     * @throws InvalidArgumentException When the position is negative
     * @throws RuntimeException When the stream is closed or cannot be repositioned
     */
    public function setPosition(int $position): void;

    /**
     * Read up to the requested number of bytes from the current position.
     *
     * Fewer bytes may be returned when the end of the stream is reached.
     *
     * @param int $length Positive number of bytes to read
     * @return string Binary data read from the stream
     * @throws InvalidArgumentException When the length is not positive
     * @throws RuntimeException When the stream is closed or cannot be read
     */
    public function read(int $length): string;

    /**
     * Write binary data at the current position.
     *
     * Implementations may write fewer bytes than requested. A zero return value indicates
     * that no progress was made.
     *
     * @param string $data Binary data to write
     * @return int Number of bytes written, from zero through the input length
     * @throws RuntimeException When the stream is closed or writing fails
     */
    public function write(string $data): int;

    /**
     * Close the stream and release its underlying resources.
     *
     * Implementations must allow repeated calls without failing.
     *
     * @return void
     * @throws RuntimeException When the stream cannot be closed
     */
    public function close(): void;
}
