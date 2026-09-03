<?php

declare(strict_types=1);

namespace Lxr\BinaryData;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Contracts\WriterContract;
use Lxr\BinaryData\Enums\BytesOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\LogicException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Traits\ByteOrderTrait;
use Lxr\BinaryData\ValueObjects\UInt64;

class Writer implements WriterContract
{
    use ByteOrderTrait;

    /**
     * Construct new Writer instance.
     *
     * @param StreamContract $resource Stream to write to
     * @return void
     */
    public function __construct(
        private StreamContract $resource,
        BytesOrder $bytesOrder = BytesOrder::NATIVE,
    ) {
        $this->bytesOrder = $bytesOrder;
    }

    /**
     * Write raw bytes to the stream.
     *
     * @param string $data Data to write
     * @return int Number of bytes written
     */
    public function writeBytes(string $data): int
    {
        return $this->resource->write($data);
    }

    /**
     * Write an 8-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 8-bit range
     */
    public function writeInt8(int $value): int
    {
        $this->assertIntegerInRange($value, -128, 127, 'signed 8-bit integer');

        $data = pack('c', $value);
        return $this->resource->write($data);
    }

    /**
     * Write a 16-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 16-bit range
     */
    public function writeInt16(int $value): int
    {
        $this->assertIntegerInRange($value, -32_768, 32_767, 'signed 16-bit integer');

        $data = $this->applyByteOrder(
            pack('s', $value)
        );

        return $this->resource->write($data);
    }

    /**
     * Write a 32-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the signed 32-bit range
     */
    public function writeInt32(int $value): int
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

        return $this->resource->write($data);
    }

    /**
     * Write a 64-bit signed integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     */
    public function writeInt64(int $value): int
    {
        $data = $this->applyByteOrder(
            pack('q', $value)
        );

        return $this->resource->write($data);
    }

    /**
     * Write an unsigned 8-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 8-bit range
     */
    public function writeUInt8(int $value): int
    {
        $this->assertIntegerInRange($value, 0, 255, 'unsigned 8-bit integer');

        $data = pack('C', $value);
        return $this->resource->write($data);
    }

    /**
     * Write an unsigned 16-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 16-bit range
     */
    public function writeUInt16(int $value): int
    {
        $this->assertIntegerInRange($value, 0, 65_535, 'unsigned 16-bit integer');

        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'S',
            BytesOrder::LITTLE_ENDIAN => 'v',
            BytesOrder::BIG_ENDIAN => 'n',
        };

        $data = pack($format, $value);
        return $this->resource->write($data);
    }

    /**
     * Write an unsigned 32-bit integer to the stream.
     *
     * @param int $value Value to write
     * @return int Number of bytes written
     * @throws OverflowException When the value is outside the unsigned 32-bit range
     */
    public function writeUInt32(int $value): int
    {
        $this->assertIntegerInRange(
            $value,
            0,
            4_294_967_295,
            'unsigned 32-bit integer'
        );

        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'L',
            BytesOrder::LITTLE_ENDIAN => 'V',
            BytesOrder::BIG_ENDIAN => 'N',
        };

        $data = pack($format, $value);
        return $this->resource->write($data);
    }

    /**
     * Write an unsigned 64-bit integer to the stream.
     *
     * @param UInt64 $value Value to write
     * @return int Number of bytes written
     */
    public function writeUInt64(UInt64 $value): int
    {
        $data = match ($this->bytesOrder->resolve()) {
            BytesOrder::BIG_ENDIAN => $value->toBigEndianBytes(),
            BytesOrder::LITTLE_ENDIAN => $value->toLittleEndianBytes(),
            BytesOrder::NATIVE => throw new LogicException(
                'Resolved byte order cannot be native'
            ),
        };

        return $this->resource->write($data);
    }

    /**
     * Write a single-precision floating-point value to the stream.
     *
     * @param float $value Value to write
     * @return int Number of bytes written
     */
    public function writeFloat(float $value): int
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'f',
            BytesOrder::LITTLE_ENDIAN => 'g',
            BytesOrder::BIG_ENDIAN => 'G',
        };

        $data = pack($format, $value);
        return $this->resource->write($data);
    }

    /**
     * Write a double-precision floating-point value to the stream.
     *
     * @param float $value Value to write
     * @return int Number of bytes written
     */
    public function writeDouble(float $value): int
    {
        $format = match ($this->bytesOrder) {
            BytesOrder::NATIVE => 'd',
            BytesOrder::LITTLE_ENDIAN => 'e',
            BytesOrder::BIG_ENDIAN => 'E',
        };

        $data = pack($format, $value);
        return $this->resource->write($data);
    }

    /**
     * Write a null-terminated string to the stream.
     *
     * @param string $value String to write
     * @return int Number of bytes written
     */
    public function writeStringWithNullTerminator(string $value): int
    {
        $data = $value . "\0";
        return $this->resource->write($data);
    }

    /**
     * Write a string preceded by its length.
     *
     * @param string $value String to write
     * @param LengthPrefixSize $lengthPrefixSize Size of the length prefix
     * @return int Number of string bytes written
     * @throws OverflowException When the string length does not fit in the prefix
     */
    public function writeStringWithLengthPrefix(
        string $value,
        LengthPrefixSize $lengthPrefixSize = LengthPrefixSize::INT
    ): int {
        $length = strlen($value);
        $maximumLength = $lengthPrefixSize->maximumValue();

        if ($length > $maximumLength) {
            throw new OverflowException(
                sprintf(
                    'String length %d exceeds the maximum value %d for a %d-byte prefix',
                    $length,
                    $maximumLength,
                    $lengthPrefixSize->value,
                )
            );
        }

        switch ($lengthPrefixSize) {
            case LengthPrefixSize::BYTE:
                $this->writeUInt8($length);
                break;
            case LengthPrefixSize::SHORT:
                $this->writeUInt16($length);
                break;
            case LengthPrefixSize::INT:
                $this->writeUInt32($length);
                break;
        }

        return $this->resource->write($value);
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
