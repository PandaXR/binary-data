<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Tests\Support;

use Lxr\BinaryData\Contracts\StreamContract;

final class MemoryStream implements StreamContract
{
    private int $position = 0;

    public function __construct(public string $data = '')
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
        $data = substr($this->data, $this->position, $length);
        $this->position += strlen($data);

        return $data;
    }

    public function write(string $data): int
    {
        $this->data = substr_replace($this->data, $data, $this->position, strlen($data));
        $length = strlen($data);
        $this->position += $length;

        return $length;
    }

    public function close(): void
    {
    }
}
