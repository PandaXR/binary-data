<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\ValueObjects\UInt64;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UInt64Test extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function unsignedValues(): iterable
    {
        yield 'zero' => [
            "\x00\x00\x00\x00\x00\x00\x00\x00",
            '0000000000000000',
            '0',
        ];
        yield 'multi-byte value' => [
            "\x01\x02\x03\x04\x05\x06\x07\x08",
            '0102030405060708',
            '72623859790382856',
        ];
        yield 'largest signed integer' => [
            "\x7f\xff\xff\xff\xff\xff\xff\xff",
            '7fffffffffffffff',
            (string) PHP_INT_MAX,
        ];
        yield 'first value above signed range' => [
            "\x80\x00\x00\x00\x00\x00\x00\x00",
            '8000000000000000',
            '9223372036854775808',
        ];
        yield 'maximum unsigned integer' => [
            "\xff\xff\xff\xff\xff\xff\xff\xff",
            'ffffffffffffffff',
            '18446744073709551615',
        ];
    }

    #[DataProvider('unsignedValues')]
    public function testPreservesEveryBitAndConvertsToDecimal(
        string $bytes,
        string $hex,
        string $decimal,
    ): void {
        $value = new UInt64($bytes);

        self::assertSame($bytes, $value->toBigEndianBytes());
        self::assertSame(strrev($bytes), $value->toLittleEndianBytes());
        self::assertSame($hex, $value->toHex());
        self::assertSame($decimal, $value->toDecimalString());
    }

    public function testConvertsRepresentableValueToInt(): void
    {
        $value = new UInt64("\x01\x02\x03\x04\x05\x06\x07\x08");

        self::assertSame(72_623_859_790_382_856, $value->toInt());
    }

    public function testConvertsLargestRepresentableValueToInt(): void
    {
        $value = new UInt64("\x7f\xff\xff\xff\xff\xff\xff\xff");

        self::assertSame(PHP_INT_MAX, $value->toInt());
    }

    public function testRejectsConversionAbovePhpIntMax(): void
    {
        $value = new UInt64("\x80\x00\x00\x00\x00\x00\x00\x00");

        $this->expectException(OverflowException::class);

        $value->toInt();
    }
}
