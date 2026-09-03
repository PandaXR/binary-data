<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Exceptions\BinaryDataException;
use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Exceptions\LogicException;
use Lxr\BinaryData\Exceptions\OverflowException;
use Lxr\BinaryData\Exceptions\RuntimeException;
use Lxr\BinaryData\Exceptions\UnexpectedValueException;
use Lxr\BinaryData\ValueObjects\UInt64;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testEveryPackageExceptionSharesTheMarkerInterface(): void
    {
        $exceptions = [
            new InvalidArgumentException(),
            new LogicException(),
            new OverflowException(),
            new RuntimeException(),
            new UnexpectedValueException(),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(BinaryDataException::class, $exception);
        }
    }

    public function testPackageExceptionsPreserveSplCompatibility(): void
    {
        self::assertInstanceOf(\InvalidArgumentException::class, new InvalidArgumentException());
        self::assertInstanceOf(\LogicException::class, new LogicException());
        self::assertInstanceOf(\OverflowException::class, new OverflowException());
        self::assertInstanceOf(\RuntimeException::class, new RuntimeException());
        self::assertInstanceOf(\UnexpectedValueException::class, new UnexpectedValueException());
    }

    public function testUInt64ThrowsPackageInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UInt64("\0");
    }

    public function testUInt64ThrowsPackageOverflowException(): void
    {
        $value = new UInt64(str_repeat("\xff", 8));

        $this->expectException(OverflowException::class);

        $value->toInt();
    }
}
