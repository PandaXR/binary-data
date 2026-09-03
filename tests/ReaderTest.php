<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Contracts\StreamContract;
use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReaderTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, ByteOrder, int}>
     */
    public static function integerReads(): iterable
    {
        yield 'signed byte' => ['readInt8', "\xfe", ByteOrder::NATIVE, -2];
        yield 'signed short big endian' => ['readInt16', "\xfe\xfd", ByteOrder::BIG_ENDIAN, -259];
        yield 'signed short little endian' => ['readInt16', "\xfd\xfe", ByteOrder::LITTLE_ENDIAN, -259];
        yield 'signed short native endian' => ['readInt16', pack('s', -259), ByteOrder::NATIVE, -259];
        yield 'signed int big endian' => [
            'readInt32',
            "\x01\x02\x03\x04",
            ByteOrder::BIG_ENDIAN,
            16_909_060,
        ];
        yield 'signed int little endian' => [
            'readInt32',
            "\x04\x03\x02\x01",
            ByteOrder::LITTLE_ENDIAN,
            16_909_060,
        ];
        yield 'signed long big endian' => [
            'readInt64',
            "\x01\x02\x03\x04\x05\x06\x07\x08",
            ByteOrder::BIG_ENDIAN,
            72_623_859_790_382_856,
        ];
        yield 'signed long little endian' => [
            'readInt64',
            "\x08\x07\x06\x05\x04\x03\x02\x01",
            ByteOrder::LITTLE_ENDIAN,
            72_623_859_790_382_856,
        ];
        yield 'unsigned byte' => ['readUInt8', "\xfe", ByteOrder::NATIVE, 254];
        yield 'unsigned short big endian' => ['readUInt16', "\x01\x02", ByteOrder::BIG_ENDIAN, 258];
        yield 'unsigned short little endian' => ['readUInt16', "\x02\x01", ByteOrder::LITTLE_ENDIAN, 258];
        yield 'unsigned short native endian' => ['readUInt16', pack('S', 258), ByteOrder::NATIVE, 258];
        yield 'unsigned int big endian' => [
            'readUInt32',
            "\x01\x02\x03\x04",
            ByteOrder::BIG_ENDIAN,
            16_909_060,
        ];
        yield 'unsigned int little endian' => [
            'readUInt32',
            "\x04\x03\x02\x01",
            ByteOrder::LITTLE_ENDIAN,
            16_909_060,
        ];
        yield 'unsigned int native endian' => [
            'readUInt32',
            pack('L', 16_909_060),
            ByteOrder::NATIVE,
            16_909_060,
        ];
    }

    #[DataProvider('integerReads')]
    public function testReadsIntegers(
        string $method,
        string $data,
        ByteOrder $byteOrder,
        int $expected,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertSame($expected, $reader->{$method}());
    }

    /**
     * @return iterable<string, array{string, string, ByteOrder, float}>
     */
    public static function floatingPointReads(): iterable
    {
        yield 'float big endian' => ['readFloat', pack('G', 1.5), ByteOrder::BIG_ENDIAN, 1.5];
        yield 'float little endian' => ['readFloat', pack('g', 1.5), ByteOrder::LITTLE_ENDIAN, 1.5];
        yield 'float native endian' => ['readFloat', pack('f', 1.5), ByteOrder::NATIVE, 1.5];
        yield 'double big endian' => ['readDouble', pack('E', -3.25), ByteOrder::BIG_ENDIAN, -3.25];
        yield 'double little endian' => ['readDouble', pack('e', -3.25), ByteOrder::LITTLE_ENDIAN, -3.25];
        yield 'double native endian' => ['readDouble', pack('d', -3.25), ByteOrder::NATIVE, -3.25];
    }

    #[DataProvider('floatingPointReads')]
    public function testReadsFloatingPointValues(
        string $method,
        string $data,
        ByteOrder $byteOrder,
        float $expected,
    ): void {
        $stream = new MemoryStream($data);
        $reader = new Reader($stream, $byteOrder);

        self::assertEqualsWithDelta($expected, $reader->{$method}(), 0.000_001);
        self::assertSame(strlen($data), $stream->getPosition());
    }

    /**
     * @return iterable<string, array{ByteOrder, string}>
     */
    public static function uint64Reads(): iterable
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

    #[DataProvider('uint64Reads')]
    public function testReadsUInt64(ByteOrder $byteOrder, string $data): void
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

    public function testCollectsPartialChunksUntilRawReadIsComplete(): void
    {
        $stream = new class ('abcd') implements StreamContract {
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
        $reader = new Reader($stream);

        self::assertSame('abcd', $reader->readBytes(4));
        self::assertSame(4, $stream->getPosition());
    }

    public function testRejectsTruncatedRawRead(): void
    {
        $reader = new Reader(new MemoryStream('abc'));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected 4 bytes, received 3');

        $reader->readBytes(4);
    }

    public function testReadsZeroRawBytesWithoutAdvancingTheStream(): void
    {
        $stream = new MemoryStream('abc');
        $reader = new Reader($stream);

        self::assertSame('', $reader->readBytes(0));
        self::assertSame(0, $stream->getPosition());
    }

    public function testRejectsNegativeRawReadLengthWithoutAdvancingTheStream(): void
    {
        $stream = new MemoryStream('abc');
        $reader = new Reader($stream);

        try {
            $reader->readBytes(-1);
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Read length must be greater than or equal to zero',
                $exception->getMessage()
            );
        }

        self::assertSame(0, $stream->getPosition());
    }

    public function testReadsUntilDelimiterAndConsumesIt(): void
    {
        $reader = new Reader($stream = new MemoryStream('alpha|omega'));

        self::assertSame('alpha', $reader->readUntil('|'));
        self::assertSame(6, $stream->getPosition());
    }

    public function testReadsUntilMultiByteDelimiterAndConsumesIt(): void
    {
        $reader = new Reader($stream = new MemoryStream('alpha<END>omega'));

        self::assertSame('alpha', $reader->readUntil('<END>'));
        self::assertSame(10, $stream->getPosition());
    }

    public function testReadsUntilDelimiterInBoundedChunksWithoutConsumingTrailingData(): void
    {
        $value = str_repeat('a', 10_000);
        $stream = new MemoryStream($value . '<END>tail');
        $reader = new Reader($stream, maximumStringLength: 20_000);

        self::assertSame($value, $reader->readUntil('<END>'));
        self::assertSame(2, $stream->readCount);
        self::assertSame(10_005, $stream->getPosition());
        self::assertSame('tail', $reader->readBytes(4));
    }

    public function testFindsDelimiterAcrossChunkBoundary(): void
    {
        $value = str_repeat('a', 8_190);
        $stream = new MemoryStream($value . '<END>tail');
        $reader = new Reader($stream, maximumStringLength: 10_000);

        self::assertSame($value, $reader->readUntil('<END>'));
        self::assertSame(2, $stream->readCount);
        self::assertSame(8_195, $stream->getPosition());
        self::assertSame('tail', $reader->readBytes(4));
    }

    public function testRejectsEmptyDelimiterBeforeReading(): void
    {
        $stream = new MemoryStream('alpha');
        $reader = new Reader($stream);

        try {
            $reader->readUntil('');
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $stream->getPosition());
        }
    }

    public function testReadsUntilEndOfStreamWhenDelimiterIsMissing(): void
    {
        $reader = new Reader(new MemoryStream('alpha'));

        self::assertSame('alpha', $reader->readUntil('|'));
    }

    public function testReadsDelimitedStringAtConfiguredLimit(): void
    {
        $reader = new Reader(
            new MemoryStream('alpha|omega'),
            maximumStringLength: 5,
        );

        self::assertSame('alpha', $reader->readUntil('|'));
    }

    public function testRejectsDelimitedStringAboveConfiguredLimit(): void
    {
        $stream = new MemoryStream('alphab|omega');
        $reader = new Reader($stream, maximumStringLength: 5);

        try {
            $reader->readUntil('|');
            self::fail('Expected an OverflowException');
        } catch (OverflowException $exception) {
            self::assertStringContainsString('configured maximum of 5 bytes', $exception->getMessage());
        }

        self::assertSame(6, $stream->getPosition());
    }

    public function testCountsPartialDelimiterAsPayloadAtEndOfStream(): void
    {
        $reader = new Reader(
            new MemoryStream('alpha:'),
            maximumStringLength: 5,
        );

        $this->expectException(OverflowException::class);

        $reader->readUntil('::');
    }

    public function testReadsNullTerminatedString(): void
    {
        $reader = new Reader($stream = new MemoryStream("alpha\0omega"));

        self::assertSame('alpha', $reader->readNullTerminatedString());
        self::assertSame(6, $stream->getPosition());
    }

    public function testRejectsNullTerminatedStringWithoutTerminator(): void
    {
        $reader = new Reader(new MemoryStream('alpha'));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected delimiter 0x00');

        $reader->readNullTerminatedString();
    }

    /**
     * @return iterable<string, array{LengthPrefixSize, ByteOrder, string, string}>
     */
    public static function lengthPrefixedStrings(): iterable
    {
        yield 'byte prefix' => [LengthPrefixSize::UINT8, ByteOrder::NATIVE, "\x03abc", 'abc'];
        yield 'short big endian' => [
            LengthPrefixSize::UINT16,
            ByteOrder::BIG_ENDIAN,
            "\x00\x03abc",
            'abc',
        ];
        yield 'short little endian' => [
            LengthPrefixSize::UINT16,
            ByteOrder::LITTLE_ENDIAN,
            "\x03\x00abc",
            'abc',
        ];
        yield 'int big endian' => [
            LengthPrefixSize::UINT32,
            ByteOrder::BIG_ENDIAN,
            "\x00\x00\x00\x03abc",
            'abc',
        ];
        yield 'empty string' => [LengthPrefixSize::UINT8, ByteOrder::NATIVE, "\x00", ''];
    }

    #[DataProvider('lengthPrefixedStrings')]
    public function testReadsLengthPrefixedStrings(
        LengthPrefixSize $prefixSize,
        ByteOrder $byteOrder,
        string $data,
        string $expected,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        self::assertSame($expected, $reader->readLengthPrefixedString($prefixSize));
    }

    public function testReadsLengthPrefixedStringAtConfiguredLimit(): void
    {
        $reader = new Reader(
            new MemoryStream("\x05alpha"),
            maximumStringLength: 5,
        );

        self::assertSame(
            'alpha',
            $reader->readLengthPrefixedString(LengthPrefixSize::UINT8)
        );
    }

    public function testReadsEmptyStringsWhenMaximumLengthIsZero(): void
    {
        $reader = new Reader(
            new MemoryStream("\x00\0|"),
            maximumStringLength: 0,
        );

        self::assertSame(
            '',
            $reader->readLengthPrefixedString(LengthPrefixSize::UINT8)
        );
        self::assertSame('', $reader->readNullTerminatedString());
        self::assertSame('', $reader->readUntil('|'));
    }

    public function testRejectsLengthPrefixAboveConfiguredLimitBeforeReadingPayload(): void
    {
        $stream = new MemoryStream("\x06abcdef");
        $reader = new Reader($stream, maximumStringLength: 5);

        try {
            $reader->readLengthPrefixedString(LengthPrefixSize::UINT8);
            self::fail('Expected an OverflowException');
        } catch (OverflowException $exception) {
            self::assertStringContainsString('String length 6', $exception->getMessage());
        }

        self::assertSame(1, $stream->getPosition());
    }

    public function testRejectsNegativeMaximumStringLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Reader(new MemoryStream(), maximumStringLength: -1);
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
        $reader = new Reader($stream, ByteOrder::BIG_ENDIAN);

        self::assertSame(16_909_060, $reader->readUInt32());
        self::assertSame(4, $stream->getPosition());
    }

    public function testRejectsTruncatedNumericValue(): void
    {
        $reader = new Reader(new MemoryStream("\x01"), ByteOrder::BIG_ENDIAN);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected 4 bytes, received 1');

        $reader->readUInt32();
    }

    /**
     * @return iterable<string, array{string, string, ByteOrder, int}>
     */
    public static function truncatedFixedWidthValues(): iterable
    {
        yield 'signed byte' => ['readInt8', '', ByteOrder::NATIVE, 1];
        yield 'signed short' => ['readInt16', "\x01", ByteOrder::BIG_ENDIAN, 2];
        yield 'signed long' => [
            'readInt64',
            str_repeat("\x01", 7),
            ByteOrder::LITTLE_ENDIAN,
            8,
        ];
        yield 'unsigned long long' => [
            'readUInt64',
            str_repeat("\x01", 7),
            ByteOrder::BIG_ENDIAN,
            8,
        ];
        yield 'float' => [
            'readFloat',
            substr(pack('G', 1.0), 1),
            ByteOrder::BIG_ENDIAN,
            strlen(pack('G', 1.0)),
        ];
        yield 'double' => [
            'readDouble',
            substr(pack('e', 1.0), 1),
            ByteOrder::LITTLE_ENDIAN,
            strlen(pack('e', 1.0)),
        ];
    }

    #[DataProvider('truncatedFixedWidthValues')]
    public function testRejectsTruncatedFixedWidthValues(
        string $method,
        string $data,
        ByteOrder $byteOrder,
        int $expectedLength,
    ): void {
        $reader = new Reader(new MemoryStream($data), $byteOrder);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            sprintf('Expected %d bytes, received %d', $expectedLength, strlen($data))
        );

        $reader->{$method}();
    }

    public function testPropagatesStreamReadFailure(): void
    {
        $failure = new RuntimeException('Synthetic read failure');
        $stream = $this->createMock(StreamContract::class);
        $stream
            ->expects(self::once())
            ->method('read')
            ->with(1)
            ->willThrowException($failure);

        $reader = new Reader($stream);

        try {
            $reader->readInt8();
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
    }

    public function testRejectsTruncatedLengthPrefixedString(): void
    {
        $reader = new Reader(new MemoryStream("\x05ab"));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected 5 bytes, received 2');

        $reader->readLengthPrefixedString(LengthPrefixSize::UINT8);
    }
}
