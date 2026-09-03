<?php

declare(strict_types=1);

namespace Lxr\BinaryData;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Contracts\WriterContract;
use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\LogicException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Traits\ByteOrderTrait;
use Lxr\BinaryData\ValueObjects\UInt64;

class Writer implements WriterContract
{
    use ByteOrderTrait;

    /**
     * Construct new Writer instance.
     *
     * @param StreamContract $stream Stream to write to
     * @return void
     */
    public function __construct(
        private StreamContract $stream,
        ByteOrder $byteOrder = ByteOrder::NATIVE,
    ) {
        $this->byteOrder = $byteOrder;
    }

    /**
     * Write raw bytes to the stream.
     *
     * @param string $data Data to write
     * @return void
     */
    public function writeBytes(string $data): void
    {
        $this->writeExactly($data);
    }

    /**
     * Write an 8-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 8-bit range
     */
    public function writeInt8(int $value): void
    {
        $this->assertIntegerInRange($value, -128, 127, 'signed 8-bit integer');

        $data = pack('c', $value);
        $this->writeExactly($data);
    }

    /**
     * Write a 16-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 16-bit range
     */
    public function writeInt16(int $value): void
    {
        $this->assertIntegerInRange($value, -32_768, 32_767, 'signed 16-bit integer');

        $data = $this->applyByteOrder(
            pack('s', $value)
        );

        $this->writeExactly($data);
    }

    /**
     * Write a 32-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the signed 32-bit range
     */
    public function writeInt32(int $value): void
    {
        $this->assertIntegerInRange(
            $value,
            -2_147_483_648,
            2_147_483_647,
            'signed 32-bit integer'
        );

        $data = $this->applyByteOrder(
            pack('l', $value)
        );

        $this->writeExactly($data);
    }

    /**
     * Write a 64-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     */
    public function writeInt64(int $value): void
    {
        $data = $this->applyByteOrder(
            pack('q', $value)
        );

        $this->writeExactly($data);
    }

    /**
     * Write an unsigned 8-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 8-bit range
     */
    public function writeUInt8(int $value): void
    {
        $this->assertIntegerInRange($value, 0, 255, 'unsigned 8-bit integer');

        $data = pack('C', $value);
        $this->writeExactly($data);
    }

    /**
     * Write an unsigned 16-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 16-bit range
     */
    public function writeUInt16(int $value): void
    {
        $this->assertIntegerInRange($value, 0, 65_535, 'unsigned 16-bit integer');

        $format = match ($this->byteOrder) {
            ByteOrder::NATIVE => 'S',
            ByteOrder::LITTLE_ENDIAN => 'v',
            ByteOrder::BIG_ENDIAN => 'n',
        };

        $data = pack($format, $value);
        $this->writeExactly($data);
    }

    /**
     * Write an unsigned 32-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return void
     * @throws OverflowException When the value is outside the unsigned 32-bit range
     */
    public function writeUInt32(int $value): void
    {
        $this->assertIntegerInRange(
            $value,
            0,
            4_294_967_295,
            'unsigned 32-bit integer'
        );

        $format = match ($this->byteOrder) {
            ByteOrder::NATIVE => 'L',
            ByteOrder::LITTLE_ENDIAN => 'V',
            ByteOrder::BIG_ENDIAN => 'N',
        };

        $data = pack($format, $value);
        $this->writeExactly($data);
    }

    /**
     * Write an unsigned 64-bit integer to the stream.
     *
     * @param UInt64 $value Value to write
     * @return void
     */
    public function writeUInt64(UInt64 $value): void
    {
        $data = match ($this->byteOrder->resolve()) {
            ByteOrder::BIG_ENDIAN => $value->toBigEndianBytes(),
            ByteOrder::LITTLE_ENDIAN => $value->toLittleEndianBytes(),
            ByteOrder::NATIVE => throw new LogicException(
                'Resolved byte order cannot be native'
            ),
        };

        $this->writeExactly($data);
    }

    /**
     * Write a single-precision floating-point value to the stream.
     *
     * @param float $value Value to write
     * @return int Number of encoded bytes written
     */
    public function writeFloat(float $value): int
    {
        $format = match ($this->byteOrder) {
            ByteOrder::NATIVE => 'f',
            ByteOrder::LITTLE_ENDIAN => 'g',
            ByteOrder::BIG_ENDIAN => 'G',
        };

        $data = pack($format, $value);
        $this->writeExactly($data);

        return strlen($data);
    }

    /**
     * Write a double-precision floating-point value to the stream.
     *
     * @param float $value Value to write
     * @return int Number of encoded bytes written
     */
    public function writeDouble(float $value): int
    {
        $format = match ($this->byteOrder) {
            ByteOrder::NATIVE => 'd',
            ByteOrder::LITTLE_ENDIAN => 'e',
            ByteOrder::BIG_ENDIAN => 'E',
        };

        $data = pack($format, $value);
        $this->writeExactly($data);

        return strlen($data);
    }

    /**
     * Write a null-terminated string to the stream.
     *
     * @param string $value String to write
     * @return int Number of encoded bytes written, including the null terminator
     * @throws InvalidArgumentException When the string contains a null byte
     */
    public function writeNullTerminatedString(string $value): int
    {
        if (str_contains($value, "\0")) {
            throw new InvalidArgumentException(
                'Null-terminated strings must not contain null bytes'
            );
        }

        $data = $value . "\0";
        $this->writeExactly($data);

        return strlen($data);
    }

    /**
     * Write a string preceded by its length.
     *
     * @param string $value String to write
     * @param LengthPrefixSize $prefixSize Size of the length prefix
     * @return int Number of encoded bytes written, including the length prefix
     * @throws OverflowException When the string length does not fit in the prefix
     */
    public function writeLengthPrefixedString(
        string $value,
        LengthPrefixSize $prefixSize = LengthPrefixSize::UINT32
    ): int {
        $length = strlen($value);
        $maximumLength = $prefixSize->maximumValue();

        if ($length > $maximumLength) {
            throw new OverflowException(
                sprintf(
                    'String length %d exceeds the maximum value %d for a %d-byte prefix',
                    $length,
                    $maximumLength,
                    $prefixSize->value,
                )
            );
        }

        switch ($prefixSize) {
            case LengthPrefixSize::UINT8:
                $this->writeUInt8($length);
                break;
            case LengthPrefixSize::UINT16:
                $this->writeUInt16($length);
                break;
            case LengthPrefixSize::UINT32:
                $this->writeUInt32($length);
                break;
        }

        $this->writeExactly($value);

        return $prefixSize->value + $length;
    }

    /**
     * Write an exact number of bytes to the stream.
     *
     * @param string $data Data to write
     * @return void
     * @throws RuntimeException When the stream cannot write all bytes
     */
    private function writeExactly(string $data): void
    {
        $length = strlen($data);
        $totalBytesWritten = 0;

        if ($length === 0) {
            $bytesWritten = $this->stream->write('');

            if ($bytesWritten !== 0) {
                throw new RuntimeException(
                    sprintf('Stream returned an invalid write count of %d', $bytesWritten)
                );
            }

            return;
        }

        while ($totalBytesWritten < $length) {
            $remainingData = substr($data, $totalBytesWritten);
            $bytesWritten = $this->stream->write($remainingData);

            if ($bytesWritten < 0 || $bytesWritten > strlen($remainingData)) {
                throw new RuntimeException(
                    sprintf('Stream returned an invalid write count of %d', $bytesWritten)
                );
            }

            if ($bytesWritten === 0) {
                throw new RuntimeException('Failed to write to stream: no progress was made');
            }

            $totalBytesWritten += $bytesWritten;
        }
    }

    /**
     * Ensure an integer can be represented by the requested binary type.
     *
     * @param int $value Value to validate
     * @param int $minimum Smallest representable value
     * @param int $maximum Largest representable value
     * @param string $type Human-readable binary type
     * @return void
     * @throws OverflowException When the value is outside the representable range
     */
    private function assertIntegerInRange(
        int $value,
        int $minimum,
        int $maximum,
        string $type
    ): void {
        if ($value < $minimum || $value > $maximum) {
            throw new OverflowException(
                sprintf(
                    'Value %d is outside the range %d to %d for a %s',
                    $value,
                    $minimum,
                    $maximum,
                    $type,
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
        return $this->stream->getPosition();
    }

    /**
     * Set the current stream position.
     *
     * @param int $position Position in bytes
     * @return void
     */
    public function setPosition(int $position): void
    {
        $this->stream->setPosition($position);
    }
}
