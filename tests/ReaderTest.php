<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Enums\BytesOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReaderTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, BytesOrder, int}>
     */
    public static function integerReads(): iterable
    {
        yield 'signed byte' => ['readInt8', "\xfe", BytesOrder::NATIVE, -2];
        yield 'signed short big endian' => ['readInt16', "\xfe\xfd", BytesOrder::BIG_ENDIAN, -259];
        yield 'signed short little endian' => ['readInt16', "\xfd\xfe", BytesOrder::LITTLE_ENDIAN, -259];
        yield 'signed short native endian' => ['readInt16', pack('s', -259), BytesOrder::NATIVE, -259];
        yield 'signed int big endian' => [
            'readInt32',
            "\x01\x02\x03\x04",
            BytesOrder::BIG_ENDIAN,
            16_909_060,
        ];
        yield 'signed int little endian' => [
            'readInt32',
            "\x04\x03\x02\x01",
            BytesOrder::LITTLE_ENDIAN,
            16_909_060,
        ];
        yield 'signed long big endian' => [
            'readInt64',
            "\x01\x02\x03\x04\x05\x06\x07\x08",
            BytesOrder::BIG_ENDIAN,
            72_623_859_790_382_856,
        ];
        yield 'signed long little endian' => [
            'readInt64',
            "\x08\x07\x06\x05\x04\x03\x02\x01",
            BytesOrder::LITTLE_ENDIAN,
            72_623_859_790_382_856,
        ];
        yield 'unsigned byte' => ['readUInt8', "\xfe", BytesOrder::NATIVE, 254];
        yield 'unsigned short big endian' => ['readUInt16', "\x01\x02", BytesOrder::BIG_ENDIAN, 258];
        yield 'unsigned short little endian' => ['readUInt16', "\x02\x01", BytesOrder::LITTLE_ENDIAN, 258];
        yield 'unsigned short native endian' => ['readUInt16', pack('S', 258), BytesOrder::NATIVE, 258];
        yield 'unsigned int big endian' => [
            'readUInt32',
            "\x01\x02\x03\x04",
            BytesOrder::BIG_ENDIAN,
            16_909_060,
        ];
        yield 'unsigned int little endian' => [
            'readUInt32',
            "\x04\x03\x02\x01",
            BytesOrder::LITTLE_ENDIAN,
            16_909_060,
        ];
        yield 'unsigned int native endian' => [
            'readUInt32',
            pack('L', 16_909_060),
            BytesOrder::NATIVE,
            16_909_060,
        ];
    }

    #[DataProvider('integerReads')]
    public function testReadsIntegers(
        string $method,
        string $data,
        BytesOrder $byteOrder,
        int $expected,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertSame($expected, $reader->{$method}());
    }

    /**
     * @return iterable<string, array{string, string, BytesOrder, float}>
     */
    public static function floatingPointReads(): iterable
    {
        yield 'float big endian' => ['readFloat', pack('G', 1.5), BytesOrder::BIG_ENDIAN, 1.5];
        yield 'float little endian' => ['readFloat', pack('g', 1.5), BytesOrder::LITTLE_ENDIAN, 1.5];
        yield 'float native endian' => ['readFloat', pack('f', 1.5), BytesOrder::NATIVE, 1.5];
        yield 'double big endian' => ['readDouble', pack('E', -3.25), BytesOrder::BIG_ENDIAN, -3.25];
        yield 'double little endian' => ['readDouble', pack('e', -3.25), BytesOrder::LITTLE_ENDIAN, -3.25];
        yield 'double native endian' => ['readDouble', pack('d', -3.25), BytesOrder::NATIVE, -3.25];
    }

    #[DataProvider('floatingPointReads')]
    public function testReadsFloatingPointValues(
        string $method,
        string $data,
        BytesOrder $byteOrder,
        float $expected,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertEqualsWithDelta($expected, $reader->{$method}(), 0.000_001);
    }

    /**
     * @return iterable<string, array{BytesOrder, string}>
     */
    public static function uint64Reads(): iterable
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

    #[DataProvider('uint64Reads')]
    public function testReadsUInt64(BytesOrder $byteOrder, string $data): void
    {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertSame('0102030405060708', $reader->readUInt64()->toHex());
    }

    public function testReadsRawBytesAndTracksPosition(): void
    {
        $stream = new MemoryStream("\x00\xffabc");
        $reader = new Reader($stream);

        self::assertSame("\x00\xff", $reader->readBytes(2));
        self::assertSame(2, $reader->getPosition());

        $reader->setPosition(1);

        self::assertSame(1, $reader->getPosition());
        self::assertSame("\xff", $reader->readBytes(1));
    }

    public function testReadsUntilDelimiterAndConsumesIt(): void
    {
        $reader = new Reader($stream = new MemoryStream('alpha|omega'));

        self::assertSame('alpha', $reader->readUntil('|'));
        self::assertSame(6, $stream->getPosition());
    }

    public function testReadsUntilEndOfStreamWhenDelimiterIsMissing(): void
    {
        $reader = new Reader(new MemoryStream('alpha'));

        self::assertSame('alpha', $reader->readUntil('|'));
    }

    public function testReadsNullTerminatedString(): void
    {
        $reader = new Reader($stream = new MemoryStream("alpha\0omega"));

        self::assertSame('alpha', $reader->readStringWithNullTerminator());
        self::assertSame(6, $stream->getPosition());
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, BytesOrder, string, string}>
     */
    public static function lengthPrefixedStrings(): iterable
    {
        yield 'byte prefix' => [LengthPrefixSize::BYTE, BytesOrder::NATIVE, "\x03abc", 'abc'];
        yield 'short big endian' => [
            LengthPrefixSize::SHORT,
            BytesOrder::BIG_ENDIAN,
            "\x00\x03abc",
            'abc',
        ];
        yield 'short little endian' => [
            LengthPrefixSize::SHORT,
            BytesOrder::LITTLE_ENDIAN,
            "\x03\x00abc",
            'abc',
        ];
        yield 'int big endian' => [
            LengthPrefixSize::INT,
            BytesOrder::BIG_ENDIAN,
            "\x00\x00\x00\x03abc",
            'abc',
        ];
        yield 'empty string' => [LengthPrefixSize::BYTE, BytesOrder::NATIVE, "\x00", ''];
    }

    #[DataProvider('lengthPrefixedStrings')]
    public function testReadsLengthPrefixedStrings(
        LengthPrefixSize $prefixSize,
        BytesOrder $byteOrder,
        string $data,
        string $expected,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertSame($expected, $reader->readStringWithLengthPrefix($prefixSize));
    }

    public function testCollectsPartialChunksUntilValueIsComplete(): void
    {
        $stream = new class ("\x01\x02\x03\x04") implements StreamContract {
            private int $position = 0;

            public function __construct(private readonly string $data)
            {
            }

            public function getPosition(): int
            {
                return $this->position;
            }

            public function setPosition(int $position): void
            {
                $this->position = $position;
            }

            public function read(int $length): string
            {
                $chunk = substr($this->data, $this->position, min(1, $length));
                $this->position += strlen($chunk);

                return $chunk;
            }

            public function write(string $data): int
            {
                return 0;
            }

            public function close(): void
            {
            }
        };
        $reader = new Reader($stream, BytesOrder::BIG_ENDIAN);

        self::assertSame(16_909_060, $reader->readUInt32());
        self::assertSame(4, $stream->getPosition());
    }

    public function testRejectsTruncatedNumericValue(): void
    {
        $reader = new Reader(new MemoryStream("\x01"), BytesOrder::BIG_ENDIAN);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected 4 bytes, received 1');

        $reader->readUInt32();
    }

    public function testRejectsTruncatedLengthPrefixedString(): void
    {
        $reader = new Reader(new MemoryStream("\x05ab"));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected 5 bytes, received 2');

        $reader->readStringWithLengthPrefix(LengthPrefixSize::BYTE);
    }
}
