<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Contracts\ReaderContract;
use Lxr\BinaryData\Contracts\WriterContract;
use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Tests\Support\MemoryStream;
use Lxr\BinaryData\Writer;
use PHPUnit\Framework\TestCase;

final class ContractsTest extends TestCase
{
    public function testReaderImplementsReaderContract(): void
    {
        self::assertInstanceOf(
            ReaderContract::class,
            new Reader(
                stream: new MemoryStream(),
                byteOrder: ByteOrder::BIG_ENDIAN,
            )
        );
    }

    public function testWriterImplementsWriterContract(): void
    {
        self::assertInstanceOf(
            WriterContract::class,
            new Writer(
                stream: new MemoryStream(),
                byteOrder: ByteOrder::BIG_ENDIAN,
            )
        );
    }
}
