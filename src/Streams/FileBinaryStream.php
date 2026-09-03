<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Streams;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\RuntimeException;

class FileBinaryStream implements StreamContract
{
    /**
     * File to read from and write to.
     *
     * @var \SplFileObject|null
     */
    private ?\SplFileObject $file = null;

    /**
     * Construct new FileBinaryStream instance.
     *
     * @param string $filePath Path to the file to read from
     * @return void
     * @throws RuntimeException When the file cannot be opened
     */
    public function __construct(string $filePath)
    {
        try {
            $this->file = new \SplFileObject($filePath, 'r+b');
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Failed to open file: ' . $filePath,
                0,
                $exception
            );
        }
    }

    /**
     * Get the current position in the stream.
     *
     * @return int Current position in bytes
     * @throws RuntimeException When the stream is closed or its position cannot be read
     */
    public function getPosition(): int
    {
        $position = $this->getFile()->ftell();

        if ($position === false) {
            throw new RuntimeException('Failed to get stream position');
        }

        return $position;
    }

    /**
     * Set the current position in the stream.
     *
     * @param int $position Zero-based byte position
     * @return void
     * @throws InvalidArgumentException When the position is negative
     * @throws RuntimeException When the stream is closed or cannot be repositioned
     */
    public function setPosition(int $position): void
    {
        if ($position < 0) {
            throw new InvalidArgumentException(
                'Stream position must be greater than or equal to zero'
            );
        }

        $result = $this->getFile()->fseek($position);

        if ($result !== 0) {
            throw new RuntimeException('Failed to set stream position');
        }
    }

    /**
     * Read a specified number of bytes from the stream.
     *
     * @param int $length Positive number of bytes to read
     * @return string Binary data read from the stream
     * @throws InvalidArgumentException When the length is not positive
     * @throws RuntimeException When the stream is closed or cannot be read
     */
    public function read(int $length): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Read length must be greater than zero');
        }

        $data = $this->getFile()->fread($length);

        if ($data === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $data;
    }

    /**
     * Write data to the stream.
     *
     * @param string $data Binary data to write
     * @return int Number of bytes written
     * @throws RuntimeException When the stream is closed or cannot write all bytes
     */
    public function write(string $data): int
    {
        $file = $this->getFile();
        $length = strlen($data);
        $totalBytesWritten = 0;

        while ($totalBytesWritten < $length) {
            $bytesWritten = $file->fwrite(substr($data, $totalBytesWritten));

            if ($bytesWritten === false) {
                throw new RuntimeException('Failed to write to stream');
            }

            if ($bytesWritten === 0) {
                throw new RuntimeException('Failed to write to stream: no progress was made');
            }

            $totalBytesWritten += $bytesWritten;
        }

        return $totalBytesWritten;
    }

    /**
     * Close the stream and release any resources.
     *
     * Repeated calls have no effect.
     *
     * @return void
     */
    public function close(): void
    {
        $this->file = null;
    }

    /**
     * Get the open file.
     *
     * @return \SplFileObject Open file
     * @throws RuntimeException When the stream has already been closed
     */
    private function getFile(): \SplFileObject
    {
        if ($this->file === null) {
            throw new RuntimeException('Stream is closed');
        }

        return $this->file;
    }
}
