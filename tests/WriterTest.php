<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Enums\BytesOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use Lxr\BinaryData\ValueObjects\UInt64;
use Lxr\BinaryData\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WriterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed, BytesOrder, string}>
     */
    public static function scalarWrites(): iterable
    {
        yield 'raw bytes' => ['writeBytes', "\x00\xff", BytesOrder::NATIVE, "\x00\xff"];
        yield 'signed byte' => ['writeInt8', -2, BytesOrder::NATIVE, "\xfe"];
        yield 'signed short big endian' => ['writeInt16', -259, BytesOrder::BIG_ENDIAN, "\xfe\xfd"];
        yield 'signed short little endian' => ['writeInt16', -259, BytesOrder::LITTLE_ENDIAN, "\xfd\xfe"];
        yield 'signed short native endian' => ['writeInt16', -259, BytesOrder::NATIVE, pack('s', -259)];
        yield 'signed int big endian' => [
            'writeInt32',
            16_909_060,
            BytesOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04",
        ];
        yield 'signed int little endian' => [
            'writeInt32',
            16_909_060,
            BytesOrder::LITTLE_ENDIAN,
            "\x04\x03\x02\x01",
        ];
        yield 'signed long big endian' => [
            'writeInt64',
            72_623_859_790_382_856,
            BytesOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04\x05\x06\x07\x08",
        ];
        yield 'signed long little endian' => [
            'writeInt64',
            72_623_859_790_382_856,
            BytesOrder::LITTLE_ENDIAN,
            "\x08\x07\x06\x05\x04\x03\x02\x01",
        ];
        yield 'unsigned byte' => ['writeUInt8', 254, BytesOrder::NATIVE, "\xfe"];
        yield 'unsigned short big endian' => ['writeUInt16', 258, BytesOrder::BIG_ENDIAN, "\x01\x02"];
        yield 'unsigned short little endian' => ['writeUInt16', 258, BytesOrder::LITTLE_ENDIAN, "\x02\x01"];
        yield 'unsigned short native endian' => ['writeUInt16', 258, BytesOrder::NATIVE, pack('S', 258)];
        yield 'unsigned int big endian' => [
            'writeUInt32',
            16_909_060,
            BytesOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04",
        ];
        yield 'unsigned int little endian' => [
            'writeUInt32',
            16_909_060,
            BytesOrder::LITTLE_ENDIAN,
            "\x04\x03\x02\x01",
        ];
        yield 'unsigned int native endian' => [
            'writeUInt32',
            16_909_060,
            BytesOrder::NATIVE,
            pack('L', 16_909_060),
        ];
        yield 'float big endian' => ['writeFloat', 1.5, BytesOrder::BIG_ENDIAN, pack('G', 1.5)];
        yield 'float little endian' => ['writeFloat', 1.5, BytesOrder::LITTLE_ENDIAN, pack('g', 1.5)];
        yield 'float native endian' => ['writeFloat', 1.5, BytesOrder::NATIVE, pack('f', 1.5)];
        yield 'double big endian' => ['writeDouble', -3.25, BytesOrder::BIG_ENDIAN, pack('E', -3.25)];
        yield 'double little endian' => ['writeDouble', -3.25, BytesOrder::LITTLE_ENDIAN, pack('e', -3.25)];
        yield 'double native endian' => ['writeDouble', -3.25, BytesOrder::NATIVE, pack('d', -3.25)];
    }

    #[DataProvider('scalarWrites')]
    public function testWritesScalarValues(
        string $method,
        mixed $value,
        BytesOrder $byteOrder,
        string $expected,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        self::assertSame(strlen($expected), $writer->{$method}($value));
        self::assertSame($expected, $stream->data);
    }

    /**
     * @return iterable<string, array{BytesOrder, string}>
     */
    public static function uint64Writes(): iterable
    {
        $bigEndian = "\x01\x02\x03\x04\x05\x06\x07\x08";

        yield 'big endian' => [BytesOrder::BIG_ENDIAN, $bigEndian];
        yield 'little endian' => [BytesOrder::LITTLE_ENDIAN, strrev($bigEndian)];
        yield 'native endian' => [
            BytesOrder::NATIVE,
            BytesOrder::NATIVE->resolve() === BytesOrder::BIG_ENDIAN
                ? $bigEndian
                : strrev($bigEndian),
        ];
    }

    #[DataProvider('uint64Writes')]
    public function testWritesUInt64(BytesOrder $byteOrder, string $expected): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);
        $value = new UInt64("\x01\x02\x03\x04\x05\x06\x07\x08");

        self::assertSame(8, $writer->writeUInt64($value));
        self::assertSame($expected, $stream->data);
    }

    public function testWritesNullTerminatedString(): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        self::assertSame(6, $writer->writeStringWithNullTerminator('alpha'));
        self::assertSame("alpha\0", $stream->data);
    }

    public function testForwardsPositionOperations(): void
    {
        $stream = new MemoryStream('abcd');
        $writer = new Writer($stream);

        $writer->setPosition(2);

        self::assertSame(2, $writer->getPosition());
        self::assertSame(1, $writer->writeBytes('X'));
        self::assertSame('abXd', $stream->data);
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, int, string}>
     */
    public static function representableLengths(): iterable
    {
        yield 'empty byte-prefixed string' => [LengthPrefixSize::BYTE, 0, "\x00"];
        yield 'largest byte-prefixed string' => [LengthPrefixSize::BYTE, 255, "\xff"];
        yield 'largest short-prefixed string' => [LengthPrefixSize::SHORT, 65_535, "\xff\xff"];
        yield 'int-prefixed string' => [LengthPrefixSize::INT, 3, "\x00\x00\x00\x03"];
    }

    #[DataProvider('representableLengths')]
    public function testWritesRepresentableLengthPrefix(
        LengthPrefixSize $prefixSize,
        int $length,
        string $expectedPrefix,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, BytesOrder::BIG_ENDIAN);
        $value = str_repeat('a', $length);

        $bytesWritten = $writer->writeStringWithLengthPrefix($value, $prefixSize);

        self::assertSame($length, $bytesWritten);
        self::assertSame($expectedPrefix . $value, $stream->data);
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, int}>
     */
    public static function overflowingLengths(): iterable
    {
        yield 'byte prefix' => [LengthPrefixSize::BYTE, 256];
        yield 'short prefix' => [LengthPrefixSize::SHORT, 65_536];
    }

    #[DataProvider('overflowingLengths')]
    public function testRejectsOverflowBeforeWriting(
        LengthPrefixSize $prefixSize,
        int $length,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        try {
            $writer->writeStringWithLengthPrefix(str_repeat('a', $length), $prefixSize);
            self::fail('Expected an OverflowException');
        } catch (OverflowException $exception) {
            self::assertStringContainsString((string) $length, $exception->getMessage());
        }

        self::assertSame('', $stream->data);
        self::assertSame(0, $stream->getPosition());
    }

    public function testLengthPrefixMaximumValues(): void
    {
        self::assertSame(255, LengthPrefixSize::BYTE->maximumValue());
        self::assertSame(65_535, LengthPrefixSize::SHORT->maximumValue());
        self::assertSame(4_294_967_295, LengthPrefixSize::INT->maximumValue());
    }
}
