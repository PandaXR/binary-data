<?php

declare(strict_types=1);

namespace Lxr\BinaryData;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Contracts\ReaderContract;
use Lxr\BinaryData\Enums\BytesOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\LogicException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\Traits\ByteOrderTrait;
use Lxr\BinaryData\ValueObjects\UInt64;

class Reader implements ReaderContract
{
    use ByteOrderTrait;

    /**
     * Default maximum length for variable-length strings, in bytes.
     */
    public const DEFAULT_MAXIMUM_STRING_LENGTH = 16_777_216;

    /**
     * Construct new Reader instance.
     *
     * @param StreamContract $resource Stream to read from
     * @param BytesOrder $bytesOrder Byte order for reading multi-byte values
     * @param int $maximumStringLength Maximum length of a variable-length string
     * @return void
     * @throws InvalidArgumentException When the maximum string length is negative
     */
    public function __construct(
        private StreamContract $resource,
        BytesOrder $bytesOrder = BytesOrder::NATIVE,
        private int $maximumStringLength = self::DEFAULT_MAXIMUM_STRING_LENGTH,
    ) {
        if ($maximumStringLength < 0) {
            throw new InvalidArgumentException(
                'Maximum string length must be greater than or equal to zero'
            );
        }

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
     * @throws InvalidArgumentException When the delimiter is empty
     * @throws OverflowException When the configured maximum string length is exceeded
     */
    public function readUntil(string $delimiter): string
    {
        return $this->readUntilDelimiter($delimiter, false);
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
     * @throws OverflowException When the configured maximum string length is exceeded
     */
    public function readStringWithLengthPrefix(
        LengthPrefixSize $prefixSize = LengthPrefixSize::INT
    ): string {
        $length = match ($prefixSize) {
            LengthPrefixSize::BYTE => $this->readUInt8(),
            LengthPrefixSize::SHORT => $this->readUInt16(),
            LengthPrefixSize::INT => $this->readUInt32(),
        };

        $this->assertStringLengthWithinLimit($length);

        return $length ? $this->readExactly($length) : '';
    }

    /**
     * Read a null-terminated string from the stream.
     *
     * @return string String preceding the null terminator
     * @throws OverflowException When the configured maximum string length is exceeded
     * @throws UnexpectedValueException When the stream ends before the null terminator
     */
    public function readStringWithNullTerminator(): string
    {
        return $this->readUntilDelimiter("\0", true);
    }

    /**
     * Read a bounded string ending at a delimiter or the end of the stream.
     *
     * @param string $delimiter Delimiter that terminates the read
     * @param bool $requireDelimiter Whether reaching the end of the stream is an error
     * @return string Bytes preceding the delimiter
     * @throws InvalidArgumentException When the delimiter is empty
     * @throws OverflowException When the configured maximum string length is exceeded
     * @throws UnexpectedValueException When a required delimiter is not found
     */
    private function readUntilDelimiter(string $delimiter, bool $requireDelimiter): string
    {
        if ($delimiter === '') {
            throw new InvalidArgumentException('Delimiter must not be empty');
        }

        $result = '';
        $delimiterLength = strlen($delimiter);

        while (true) {
            $char = $this->resource->read(1);
            if ($char === '') {
                $this->assertStringLengthWithinLimit(strlen($result));

                if ($requireDelimiter) {
                    throw new UnexpectedValueException(
                        sprintf(
                            'Expected delimiter 0x%s before the end of the stream',
                            bin2hex($delimiter)
                        )
                    );
                }

                return $result;
            }

            $result .= $char;

            if (str_ends_with($result, $delimiter)) {
                $valueLength = strlen($result) - $delimiterLength;
                $this->assertStringLengthWithinLimit($valueLength);

                return substr($result, 0, $valueLength);
            }

            $possibleDelimiterLength = min($delimiterLength - 1, strlen($result));
            $minimumValueLength = strlen($result) - $possibleDelimiterLength;
            $this->assertStringLengthWithinLimit($minimumValueLength);
        }
    }

    /**
     * Ensure a string length does not exceed the configured maximum.
     *
     * @param int $length String length to validate
     * @return void
     * @throws OverflowException When the configured maximum string length is exceeded
     */
    private function assertStringLengthWithinLimit(int $length): void
    {
        if ($length > $this->maximumStringLength) {
            throw new OverflowException(
                sprintf(
                    'String length %d exceeds the configured maximum of %d bytes',
                    $length,
                    $this->maximumStringLength,
                )
            );
        }
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
