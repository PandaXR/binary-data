<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Contracts;

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
     */
    public function getPosition(): int;

    /**
     * Move the stream cursor to an absolute byte position.
     *
     * @param int $position Zero-based byte position
     * @return void
     */
    public function setPosition(int $position): void;

    /**
     * Read up to the requested number of bytes from the current position.
     *
     * Fewer bytes may be returned when the end of the stream is reached.
     *
     * @param int $length Positive number of bytes to read
     * @return string Binary data read from the stream
     */
    public function read(int $length): string;

    /**
     * Write binary data at the current position.
     *
     * @param string $data Binary data to write
     * @return int Number of bytes written
     */
    public function write(string $data): int;

    /**
     * Close the stream and release its underlying resources.
     *
     * @return void
     */
    public function close(): void;
}
