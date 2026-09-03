<?php

declare(strict_types=1);

namespace Lxr\BinaryData;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Contracts\ReaderContract;
use Lxr\BinaryData\Enums\BytesOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\LogicException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\Traits\ByteOrderTrait;
use Lxr\BinaryData\ValueObjects\UInt64;

class Reader implements ReaderContract
{
    use ByteOrderTrait;

    /**
     * Construct new Reader instance.
     *
     * @param StreamContract $resource Stream to read from
     * @param BytesOrder $bytesOrder Byte order for reading multi-byte values
     * @return void
     */
    public function __construct(
        private StreamContract $resource,
        BytesOrder $bytesOrder = BytesOrder::NATIVE,
    ) {
        $this->bytesOrder = $bytesOrder;
    }

    /**
     * Read raw bytes from the stream.
     *
     * @param int $length Number of bytes to read
     * @return string Bytes read from the stream
     */
    public function readBytes(int $length): string
    {
        return $this->resource->read($length);
    }

    /**
     * Read bytes until the delimiter is encountered.
     *
     * @param string $delimiter Delimiter that terminates the read
     * @return string Bytes preceding the delimiter
     */
    public function readUntil(string $delimiter): string
    {
        $result = '';

        while (true) {
            $char = $this->resource->read(1);
            if ($char === '' || $char === $delimiter) {
                break;
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * Read an exact number of bytes from the stream.
     *
     * @param int $length Number of bytes to read
     * @return string Requested binary data
     * @throws UnexpectedValueException When the stream ends before all bytes are read
     */
    private function readExactly(int $length): string
    {
        $data = '';

        while (strlen($data) < $length) {
            $chunk = $this->resource->read($length - strlen($data));
            if ($chunk === '') {
                throw new UnexpectedValueException(
                    sprintf('Expected %d bytes, received %d', $length, strlen($data))
                );
            }

            $data .= $chunk;
        }

        return $data;
    }

    /**
     * Unpack binary data using the specified format.
     *
     * @param string $format Format accepted by unpack
     * @param string $data Binary data to unpack
     * @return mixed Unpacked value
     * @throws RuntimeException When the data cannot be unpacked
     */
    private function unpack(string $format, string $data): mixed
    {
        $result = unpack($format, $data);
        if ($result === false) {
            throw new RuntimeException('Failed to unpack data');
        }

        return $result[1];
    }

    /**
     * Read an 8-bit signed integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readInt8(): int
    {
        $data = $this->readExactly(1);
        return (int) $this->unpack('c', $data);
    }

    /**
     * Read a 16-bit signed integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readInt16(): int
    {
        $data = $this->applyByteOrder(
            $this->readExactly(2)
        );

        return (int) $this->unpack('s', $data);
    }

    /**
     * Read a 32-bit signed integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readInt32(): int
    {
        $data = $this->applyByteOrder(
            $this->readExactly(4)
        );

        return (int) $this->unpack('l', $data);
    }

    /**
     * Read a 64-bit signed integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readInt64(): int
    {
        $data = $this->applyByteOrder(
            $this->readExactly(8)
        );

        return (int) $this->unpack('q', $data);
    }

    /**
     * Read an unsigned 8-bit integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readUInt8(): int
    {
        $data = $this->readExactly(1);
        return (int) $this->unpack('C', $data);
    }

    /**
     * Read an unsigned 16-bit integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readUInt16(): int
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'S',
            BytesOrder::LITTLE_ENDIAN => 'v',
            BytesOrder::BIG_ENDIAN => 'n',
        };

        $data = $this->readExactly(2);
        return (int) $this->unpack($format, $data);
    }

    /**
     * Read an unsigned 32-bit integer from the stream.
     *
     * @return int Value read from the stream
     */
    public function readUInt32(): int
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'L',
            BytesOrder::LITTLE_ENDIAN => 'V',
            BytesOrder::BIG_ENDIAN => 'N',
        };

        $data = $this->readExactly(4);
        return (int) $this->unpack($format, $data);
    }

    /**
     * Read an unsigned 64-bit integer from the stream.
     *
     * @return UInt64 Value read from the stream
     */
    public function readUInt64(): UInt64
    {
        $data = $this->readExactly(8);

        $data = match ($this->bytesOrder->resolve()) {
            BytesOrder::BIG_ENDIAN => $data,
            BytesOrder::LITTLE_ENDIAN => strrev($data),
            BytesOrder::NATIVE => throw new LogicException(
                'Resolved byte order cannot be native'
            ),
        };

        return new UInt64($data);
    }

    /**
     * Read a single-precision floating-point value from the stream.
     *
     * @return float Value read from the stream
     */
    public function readFloat(): float
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'f',
            BytesOrder::LITTLE_ENDIAN => 'g',
            BytesOrder::BIG_ENDIAN => 'G',
        };

        $data = $this->readExactly(4);
        return (float) $this->unpack($format, $data);
    }

    /**
     * Read a double-precision floating-point value from the stream.
     *
     * @return float Value read from the stream
     */
    public function readDouble(): float
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'd',
            BytesOrder::LITTLE_ENDIAN => 'e',
            BytesOrder::BIG_ENDIAN => 'E',
        };

        $data = $this->readExactly(8);
        return (float) $this->unpack($format, $data);
    }

    /**
     * Read a string preceded by its length.
     *
     * @param LengthPrefixSize $prefixSize Size of the length prefix
     * @return string String read from the stream
     */
    public function readStringWithLengthPrefix(
        LengthPrefixSize $prefixSize = LengthPrefixSize::INT
    ): string {
        $length = match ($prefixSize) {
            LengthPrefixSize::BYTE => $this->readUInt8(),
            LengthPrefixSize::SHORT => $this->readUInt16(),
            LengthPrefixSize::INT => $this->readUInt32(),
        };

        return $length ? $this->readExactly($length) : '';
    }

    /**
     * Read a null-terminated string from the stream.
     *
     * @return string String preceding the null terminator
     */
    public function readStringWithNullTerminator(): string
    {
        return $this->readUntil("\0");
    }

    /**
     * Get the current stream position.
     *
     * @return int Current position in bytes
     */
    public function getPosition(): int
    {
        return $this->resource->getPosition();
    }

    /**
     * Set the current stream position.
     *
     * @param int $position Position in bytes
     * @return void
     */
    public function setPosition(int $position): void
    {
        $this->resource->setPosition($position);
    }
}
