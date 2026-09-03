<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Streams;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\RuntimeException;

class FileBinaryStream implements StreamContract
{
    /**
     * Resource to read from.
     *
     * @var resource
     */
    private $resource;

    /**
     * Construct new FileBinaryStream instance.
     *
     * @param string $filePath Path to the file to read from
     * @return void
     */
    public function __construct(string $filePath)
    {
        $resource = fopen($filePath, 'r+b');
        if (!$resource) {
            throw new RuntimeException('Failed to open file: ' . $filePath);
        }

        $this->resource = $resource;
    }

    /**
     * Get the current position in the stream.
     *
     * @return int
     */
    public function getPosition(): int
    {
        return (int) ftell($this->resource);
    }

    /**
     * Set the current position in the stream.
     *
     * @param int $position
     * @return void
     */
    public function setPosition(int $position): void
    {
        fseek($this->resource, $position);
    }

    /**
     * Read a specified number of bytes from the stream.
     *
     * @param int $length
     * @return string
     */
    public function read(int $length): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Read length must be greater than zero');
        }

        $data = fread($this->resource, $length);
        if ($data === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $data;
    }

    /**
     * Write data to the stream.
     *
     * @param string $data
     * @return int Number of bytes written
     */
    public function write(string $data): int
    {
        $bytesWritten = fwrite($this->resource, $data);
        if ($bytesWritten === false) {
            throw new RuntimeException('Failed to write to stream');
        }

        if ($bytesWritten !== strlen($data)) {
            throw new RuntimeException('Failed to write all data to stream');
        }

        return $bytesWritten;
    }

    /**
     * Close the stream and release any resources.
     *
     * @return void
     */
    public function close(): void
    {
        fclose($this->resource);
    }
}
