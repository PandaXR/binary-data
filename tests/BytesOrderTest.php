<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Enums\BytesOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BytesOrderTest extends TestCase
{
    /**
     * @return iterable<string, array{BytesOrder}>
     */
    public static function explicitByteOrders(): iterable
    {
        yield 'little endian' => [BytesOrder::LITTLE_ENDIAN];
        yield 'big endian' => [BytesOrder::BIG_ENDIAN];
    }

    #[DataProvider('explicitByteOrders')]
    public function testExplicitByteOrderResolvesToItself(BytesOrder $byteOrder): void
    {
        self::assertSame($byteOrder, $byteOrder->resolve());
    }

    public function testNativeByteOrderResolvesToRuntimeByteOrder(): void
    {
        $expected = pack('S', 0x0102) === "\x02\x01"
            ? BytesOrder::LITTLE_ENDIAN
            : BytesOrder::BIG_ENDIAN;

        self::assertSame($expected, BytesOrder::NATIVE->resolve());
    }
}
