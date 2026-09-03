<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use Lxr\BinaryData\ValueObjects\UInt64;
use Lxr\BinaryData\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoundTripTest extends TestCase
{
    /**
     * @return iterable<string, array{ByteOrder}>
     */
    public static function byteOrders(): iterable
    {
        yield 'native byte order' => [ByteOrder::NATIVE];
        yield 'little-endian byte order' => [ByteOrder::LITTLE_ENDIAN];
        yield 'big-endian byte order' => [ByteOrder::BIG_ENDIAN];
    }

    #[DataProvider('byteOrders')]
    public function testRoundTripsIntegerBoundaries(ByteOrder $byteOrder): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        $operations = [
            ['writeInt8', 'readInt8', -128],
            ['writeInt8', 'readInt8', 127],
            ['writeInt16', 'readInt16', -32_768],
            ['writeInt16', 'readInt16', 32_767],
            ['writeInt32', 'readInt32', -2_147_483_648],
            ['writeInt32', 'readInt32', 2_147_483_647],
            ['writeInt64', 'readInt64', PHP_INT_MIN],
            ['writeInt64', 'readInt64', PHP_INT_MAX],
            ['writeUInt8', 'readUInt8', 0],
            ['writeUInt8', 'readUInt8', 255],
            ['writeUInt16', 'readUInt16', 0],
            ['writeUInt16', 'readUInt16', 65_535],
            ['writeUInt32', 'readUInt32', 0],
            ['writeUInt32', 'readUInt32', 4_294_967_295],
        ];

        foreach ($operations as [$writeMethod, , $value]) {
            $writer->{$writeMethod}($value);
        }

        $reader = new Reader($stream, $byteOrder);
        $reader->setPosition(0);

        foreach ($operations as [, $readMethod, $expected]) {
            self::assertSame($expected, $reader->{$readMethod}());
        }

        self::assertSame(strlen($stream->data), $reader->getPosition());
    }

    #[DataProvider('byteOrders')]
    public function testRoundTripsEntireUInt64Range(ByteOrder $byteOrder): void
    {
        $values = [
            new UInt64("\x00\x00\x00\x00\x00\x00\x00\x00"),
            new UInt64("\x80\x00\x00\x00\x00\x00\x00\x00"),
            new UInt64("\xff\xff\xff\xff\xff\xff\xff\xff"),
        ];
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        foreach ($values as $value) {
            $writer->writeUInt64($value);
        }

        $reader = new Reader($stream, $byteOrder);
        $reader->setPosition(0);

        foreach ($values as $expected) {
            self::assertSame($expected->toHex(), $reader->readUInt64()->toHex());
        }
    }

    #[DataProvider('byteOrders')]
    public function testRoundTripsFiniteAndSpecialFloatingPointValues(
        ByteOrder $byteOrder
    ): void {
        $values = [0.0, -123.5, INF, -INF, NAN];
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        foreach ($values as $value) {
            $writer->writeFloat($value);
            $writer->writeDouble($value);
        }

        $reader = new Reader($stream, $byteOrder);
        $reader->setPosition(0);

        foreach ($values as $expected) {
            $float = $reader->readFloat();
            $double = $reader->readDouble();

            if (is_nan($expected)) {
                self::assertTrue(is_nan($float));
                self::assertTrue(is_nan($double));
                continue;
            }

            self::assertSame($expected, $float);
            self::assertSame($expected, $double);
        }
    }

    /**
     * @return iterable<string, array{ByteOrder, LengthPrefixSize, string}>
     */
    public static function lengthPrefixedStrings(): iterable
    {
        $values = [
            'empty byte-prefixed string' => [LengthPrefixSize::UINT8, ''],
            'maximum byte-prefixed string' => [LengthPrefixSize::UINT8, str_repeat('a', 255)],
            'maximum short-prefixed string' => [
                LengthPrefixSize::UINT16,
                str_repeat("a\0", 32_767) . 'a',
            ],
            'binary int-prefixed string' => [LengthPrefixSize::UINT32, "\x00binary\xff"],
        ];

        foreach (ByteOrder::cases() as $byteOrder) {
            foreach ($values as $description => [$prefixSize, $value]) {
                yield $byteOrder->name . ': ' . $description => [
                    $byteOrder,
                    $prefixSize,
                    $value,
                ];
            }
        }
    }

    #[DataProvider('lengthPrefixedStrings')]
    public function testRoundTripsLengthPrefixedStringsAtTheirBoundaries(
        ByteOrder $byteOrder,
        LengthPrefixSize $prefixSize,
        string $value,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        self::assertSame(
            $prefixSize->value + strlen($value),
            $writer->writeLengthPrefixedString($value, $prefixSize)
        );

        $reader = new Reader(
            $stream,
            $byteOrder,
            maximumStringLength: strlen($value),
        );
        $reader->setPosition(0);

        self::assertSame($value, $reader->readLengthPrefixedString($prefixSize));
        self::assertSame(strlen($stream->data), $reader->getPosition());
    }

    #[DataProvider('byteOrders')]
    public function testRoundTripsNullTerminatedStrings(ByteOrder $byteOrder): void
    {
        $values = ['', 'alpha', "binary\xff"];
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        foreach ($values as $value) {
            self::assertSame(
                strlen($value) + 1,
                $writer->writeNullTerminatedString($value)
            );
        }

        $reader = new Reader($stream, $byteOrder);
        $reader->setPosition(0);

        foreach ($values as $expected) {
            self::assertSame($expected, $reader->readNullTerminatedString());
        }
    }
}
