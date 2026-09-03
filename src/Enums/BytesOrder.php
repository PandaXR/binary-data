<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Enums;

/**
 * Byte orders supported when reading and writing multi-byte values.
 */
enum BytesOrder: int
{
    /** Use the byte order of the current PHP runtime. */
    case NATIVE = 0;

    /** Store the least significant byte first. */
    case LITTLE_ENDIAN = 1;

    /** Store the most significant byte first. */
    case BIG_ENDIAN = 2;

    /**
     * Resolve native byte order to the runtime's concrete byte order.
     *
     * Explicit little-endian and big-endian values are returned unchanged.
     *
     * @return self Either self::LITTLE_ENDIAN or self::BIG_ENDIAN
     */
    public function resolve(): self
    {
        if ($this !== self::NATIVE) {
            return $this;
        }

        return pack('S', 0x0102) === "\x02\x01"
            ? self::LITTLE_ENDIAN
            : self::BIG_ENDIAN;
    }
}
