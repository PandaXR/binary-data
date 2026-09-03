<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use Lxr\BinaryData\ValueObjects\UInt64;
use Lxr\BinaryData\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WriterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed, ByteOrder, string}>
     */
    public static function scalarWrites(): iterable
    {
        yield 'raw bytes' => ['writeBytes', "\x00\xff", ByteOrder::NATIVE, "\x00\xff"];
        yield 'signed byte' => ['writeInt8', -2, ByteOrder::NATIVE, "\xfe"];
        yield 'signed short big endian' => ['writeInt16', -259, ByteOrder::BIG_ENDIAN, "\xfe\xfd"];
        yield 'signed short little endian' => ['writeInt16', -259, ByteOrder::LITTLE_ENDIAN, "\xfd\xfe"];
        yield 'signed short native endian' => ['writeInt16', -259, ByteOrder::NATIVE, pack('s', -259)];
        yield 'signed int big endian' => [
            'writeInt32',
            16_909_060,
            ByteOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04",
        ];
        yield 'signed int little endian' => [
            'writeInt32',
            16_909_060,
            ByteOrder::LITTLE_ENDIAN,
            "\x04\x03\x02\x01",
        ];
        yield 'signed long big endian' => [
            'writeInt64',
            72_623_859_790_382_856,
            ByteOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04\x05\x06\x07\x08",
        ];
        yield 'signed long little endian' => [
            'writeInt64',
            72_623_859_790_382_856,
            ByteOrder::LITTLE_ENDIAN,
            "\x08\x07\x06\x05\x04\x03\x02\x01",
        ];
        yield 'unsigned byte' => ['writeUInt8', 254, ByteOrder::NATIVE, "\xfe"];
        yield 'unsigned short big endian' => ['writeUInt16', 258, ByteOrder::BIG_ENDIAN, "\x01\x02"];
        yield 'unsigned short little endian' => ['writeUInt16', 258, ByteOrder::LITTLE_ENDIAN, "\x02\x01"];
        yield 'unsigned short native endian' => ['writeUInt16', 258, ByteOrder::NATIVE, pack('S', 258)];
        yield 'unsigned int big endian' => [
            'writeUInt32',
            16_909_060,
            ByteOrder::BIG_ENDIAN,
            "\x01\x02\x03\x04",
        ];
        yield 'unsigned int little endian' => [
            'writeUInt32',
            16_909_060,
            ByteOrder::LITTLE_ENDIAN,
            "\x04\x03\x02\x01",
        ];
        yield 'unsigned int native endian' => [
            'writeUInt32',
            16_909_060,
            ByteOrder::NATIVE,
            pack('L', 16_909_060),
        ];
        yield 'float big endian' => ['writeFloat', 1.5, ByteOrder::BIG_ENDIAN, pack('G', 1.5)];
        yield 'float little endian' => ['writeFloat', 1.5, ByteOrder::LITTLE_ENDIAN, pack('g', 1.5)];
        yield 'float native endian' => ['writeFloat', 1.5, ByteOrder::NATIVE, pack('f', 1.5)];
        yield 'double big endian' => ['writeDouble', -3.25, ByteOrder::BIG_ENDIAN, pack('E', -3.25)];
        yield 'double little endian' => ['writeDouble', -3.25, ByteOrder::LITTLE_ENDIAN, pack('e', -3.25)];
        yield 'double native endian' => ['writeDouble', -3.25, ByteOrder::NATIVE, pack('d', -3.25)];
    }

    #[DataProvider('scalarWrites')]
    public function testWritesScalarValues(
        string $method,
        mixed $value,
        ByteOrder $byteOrder,
        string $expected,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);

        $result = $writer->{$method}($value);

        if (in_array($method, ['writeFloat', 'writeDouble'], true)) {
            self::assertSame(strlen($expected), $result);
        } else {
            self::assertNull($result);
        }

        self::assertSame($expected, $stream->data);
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function integerBoundaries(): iterable
    {
        yield 'signed byte minimum' => ['writeInt8', -128, '80'];
        yield 'signed byte maximum' => ['writeInt8', 127, '7f'];
        yield 'signed short minimum' => ['writeInt16', -32_768, '8000'];
        yield 'signed short maximum' => ['writeInt16', 32_767, '7fff'];
        yield 'signed int minimum' => ['writeInt32', -2_147_483_648, '80000000'];
        yield 'signed int maximum' => ['writeInt32', 2_147_483_647, '7fffffff'];
        yield 'signed long minimum' => ['writeInt64', PHP_INT_MIN, '8000000000000000'];
        yield 'signed long maximum' => ['writeInt64', PHP_INT_MAX, '7fffffffffffffff'];
        yield 'unsigned byte minimum' => ['writeUInt8', 0, '00'];
        yield 'unsigned byte maximum' => ['writeUInt8', 255, 'ff'];
        yield 'unsigned short minimum' => ['writeUInt16', 0, '0000'];
        yield 'unsigned short maximum' => ['writeUInt16', 65_535, 'ffff'];
        yield 'unsigned int minimum' => ['writeUInt32', 0, '00000000'];
        yield 'unsigned int maximum' => ['writeUInt32', 4_294_967_295, 'ffffffff'];
    }

    #[DataProvider('integerBoundaries')]
    public function testWritesIntegerBoundaries(
        string $method,
        int $value,
        string $expectedHex,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, ByteOrder::BIG_ENDIAN);

        $writer->{$method}($value);
        self::assertSame($expectedHex, bin2hex($stream->data));
    }

    /**
     * @return iterable<string, array{string, int, int, int}>
     */
    public static function outOfRangeIntegers(): iterable
    {
        yield 'signed byte underflow' => ['writeInt8', -129, -128, 127];
        yield 'signed byte overflow' => ['writeInt8', 128, -128, 127];
        yield 'signed short underflow' => ['writeInt16', -32_769, -32_768, 32_767];
        yield 'signed short overflow' => ['writeInt16', 32_768, -32_768, 32_767];
        yield 'signed int underflow' => [
            'writeInt32',
            -2_147_483_649,
            -2_147_483_648,
            2_147_483_647,
        ];
        yield 'signed int overflow' => [
            'writeInt32',
            2_147_483_648,
            -2_147_483_648,
            2_147_483_647,
        ];
        yield 'unsigned byte underflow' => ['writeUInt8', -1, 0, 255];
        yield 'unsigned byte overflow' => ['writeUInt8', 256, 0, 255];
        yield 'unsigned short underflow' => ['writeUInt16', -1, 0, 65_535];
        yield 'unsigned short overflow' => ['writeUInt16', 65_536, 0, 65_535];
        yield 'unsigned int underflow' => ['writeUInt32', -1, 0, 4_294_967_295];
        yield 'unsigned int overflow' => ['writeUInt32', 4_294_967_296, 0, 4_294_967_295];
    }

    #[DataProvider('outOfRangeIntegers')]
    public function testRejectsOutOfRangeIntegerBeforeWriting(
        string $method,
        int $value,
        int $minimum,
        int $maximum,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        try {
            $writer->{$method}($value);
            self::fail('Expected an OverflowException');
        } catch (OverflowException $exception) {
            self::assertStringContainsString((string) $value, $exception->getMessage());
            self::assertStringContainsString((string) $minimum, $exception->getMessage());
            self::assertStringContainsString((string) $maximum, $exception->getMessage());
        }

        self::assertSame('', $stream->data);
        self::assertSame(0, $stream->getPosition());
    }

    /**
     * @return iterable<string, array{ByteOrder, string}>
     */
    public static function uint64Writes(): iterable
    {
        $bigEndian = "\x01\x02\x03\x04\x05\x06\x07\x08";

        yield 'big endian' => [ByteOrder::BIG_ENDIAN, $bigEndian];
        yield 'little endian' => [ByteOrder::LITTLE_ENDIAN, strrev($bigEndian)];
        yield 'native endian' => [
            ByteOrder::NATIVE,
            ByteOrder::NATIVE->resolve() === ByteOrder::BIG_ENDIAN
                ? $bigEndian
                : strrev($bigEndian),
        ];
    }

    #[DataProvider('uint64Writes')]
    public function testWritesUInt64(ByteOrder $byteOrder, string $expected): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream, $byteOrder);
        $value = new UInt64("\x01\x02\x03\x04\x05\x06\x07\x08");

        $writer->writeUInt64($value);
        self::assertSame($expected, $stream->data);
    }

    public function testWritesNullTerminatedString(): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        self::assertSame(6, $writer->writeNullTerminatedString('alpha'));
        self::assertSame("alpha\0", $stream->data);
    }

    public function testRejectsEmbeddedNullByteBeforeWriting(): void
    {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        try {
            $writer->writeNullTerminatedString("alpha\0omega");
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame('', $stream->data);
            self::assertSame(0, $stream->getPosition());
        }
    }

    public function testCompletesPartialWrites(): void
    {
        $writtenData = '';
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::exactly(3))
            ->method('write')
            ->willReturnCallback(
                static function (string $data) use (&$writtenData): int {
                    $chunk = substr($data, 0, 2);
                    $writtenData .= $chunk;
                    return strlen($chunk);
                }
            );

        $writer = new Writer($stream);

        $writer->writeBytes('abcdef');
        self::assertSame('abcdef', $writtenData);
    }

    public function testCompletesPartialLengthPrefixedWrite(): void
    {
        $writtenData = '';
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::exactly(4))
            ->method('write')
            ->willReturnCallback(
                static function (string $data) use (&$writtenData): int {
                    $chunk = substr($data, 0, 1);
                    $writtenData .= $chunk;
                    return strlen($chunk);
                }
            );

        $writer = new Writer($stream);

        self::assertSame(
            4,
            $writer->writeLengthPrefixedString('abc', LengthPrefixSize::UINT8)
        );
        self::assertSame("\x03abc", $writtenData);
    }

    public function testRejectsWriteThatMakesNoProgress(): void
    {
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::once())
            ->method('write')
            ->with('data')
            ->willReturn(0);

        $writer = new Writer($stream);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no progress was made');

        $writer->writeBytes('data');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidWriteCounts(): iterable
    {
        yield 'negative count' => [-1];
        yield 'count above input length' => [5];
    }

    #[DataProvider('invalidWriteCounts')]
    public function testRejectsInvalidWriteCount(int $bytesWritten): void
    {
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::once())
            ->method('write')
            ->with('data')
            ->willReturn($bytesWritten);

        $writer = new Writer($stream);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid write count');

        $writer->writeBytes('data');
    }

    public function testRejectsInvalidWriteCountForEmptyData(): void
    {
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::once())
            ->method('write')
            ->with('')
            ->willReturn(1);

        $writer = new Writer($stream);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid write count');

        $writer->writeBytes('');
    }

    public function testPropagatesStreamWriteFailure(): void
    {
        $failure = new RuntimeException('Synthetic write failure');
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::once())
            ->method('write')
            ->with('data')
            ->willThrowException($failure);

        $writer = new Writer($stream);

        try {
            $writer->writeBytes('data');
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
    }

    public function testForwardsPositionOperations(): void
    {
        $stream = new MemoryStream('abcd');
        $writer = new Writer($stream);

        $writer->setPosition(2);

        self::assertSame(2, $writer->getPosition());
        $writer->writeBytes('X');
        self::assertSame('abXd', $stream->data);
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, int, string}>
     */
    public static function representableLengths(): iterable
    {
        yield 'empty byte-prefixed string' => [LengthPrefixSize::UINT8, 0, "\x00"];
        yield 'largest byte-prefixed string' => [LengthPrefixSize::UINT8, 255, "\xff"];
        yield 'largest short-prefixed string' => [LengthPrefixSize::UINT16, 65_535, "\xff\xff"];
        yield 'int-prefixed string' => [LengthPrefixSize::UINT32, 3, "\x00\x00\x00\x03"];
    }

    #[DataProvider('representableLengths')]
    public function testWritesRepresentableLengthPrefix(
        LengthPrefixSize $prefixSize,
        int $length,
        string $expectedPrefix,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream, ByteOrder::BIG_ENDIAN);
        $value = str_repeat('a', $length);

        $bytesWritten = $writer->writeLengthPrefixedString($value, $prefixSize);

        self::assertSame($prefixSize->value + $length, $bytesWritten);
        self::assertSame($expectedPrefix . $value, $stream->data);
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, int}>
     */
    public static function overflowingLengths(): iterable
    {
        yield 'byte prefix' => [LengthPrefixSize::UINT8, 256];
        yield 'short prefix' => [LengthPrefixSize::UINT16, 65_536];
    }

    #[DataProvider('overflowingLengths')]
    public function testRejectsOverflowBeforeWriting(
        LengthPrefixSize $prefixSize,
        int $length,
    ): void {
        $stream = new MemoryStream();
        $writer = new Writer($stream);

        try {
            $writer->writeLengthPrefixedString(str_repeat('a', $length), $prefixSize);
            self::fail('Expected an OverflowException');
        } catch (OverflowException $exception) {
            self::assertStringContainsString((string) $length, $exception->getMessage());
        }

        self::assertSame('', $stream->data);
        self::assertSame(0, $stream->getPosition());
    }

    public function testLengthPrefixMaximumValues(): void
    {
        self::assertSame(255, LengthPrefixSize::UINT8->maximumValue());
        self::assertSame(65_535, LengthPrefixSize::UINT16->maximumValue());
        self::assertSame(4_294_967_295, LengthPrefixSize::UINT32->maximumValue());
    }
}
