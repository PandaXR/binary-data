<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests;

use Lxr\BinaryData\Exceptions\InvalidArgumentException;
use Lxr\BinaryData\Streams\FileBinaryStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileBinaryStreamTest extends TestCase
{
    private string $filePath;

    protected function setUp(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'binary-data-');
        self::assertNotFalse($filePath);
        $this->filePath = $filePath;
    }

    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    public function testReadsWritesAndSeeksInBinaryFile(): void
    {
        file_put_contents($this->filePath, "\x00\x01\x02\x03");
        $stream = new FileBinaryStream($this->filePath);

        try {
            self::assertSame(0, $stream->getPosition());
            self::assertSame("\x00\x01", $stream->read(2));
            self::assertSame(2, $stream->getPosition());

            $stream->setPosition(1);

            self::assertSame(2, $stream->write("\xaa\xbb"));
            self::assertSame(3, $stream->getPosition());

            $stream->setPosition(0);

            self::assertSame("\x00\xaa\xbb\x03", $stream->read(4));
            self::assertSame('', $stream->read(1));
        } finally {
            $stream->close();
        }

        self::assertSame("\x00\xaa\xbb\x03", file_get_contents($this->filePath));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidReadLengths(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    #[DataProvider('invalidReadLengths')]
    public function testRejectsInvalidReadLength(int $length): void
    {
        $stream = new FileBinaryStream($this->filePath);

        try {
            $this->expectException(InvalidArgumentException::class);
            $stream->read($length);
        } finally {
            $stream->close();
        }
    }
}
