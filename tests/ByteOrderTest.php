<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Enums\ByteOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByteOrderTest extends TestCase
{
    /**
     * @return iterable<string, array{ByteOrder}>
     */
    public static function explicitByteOrders(): iterable
    {
        yield 'little endian' => [ByteOrder::LITTLE_ENDIAN];
        yield 'big endian' => [ByteOrder::BIG_ENDIAN];
    }

    #[DataProvider('explicitByteOrders')]
    public function testExplicitByteOrderResolvesToItself(ByteOrder $byteOrder): void
    {
        self::assertSame($byteOrder, $byteOrder->resolve());
    }

    public function testNativeByteOrderResolvesToRuntimeByteOrder(): void
    {
        $expected = pack('S', 0x0102) === "\x02\x01"
            ? ByteOrder::LITTLE_ENDIAN
            : ByteOrder::BIG_ENDIAN;

        self::assertSame($expected, ByteOrder::NATIVE->resolve());
    }
}
