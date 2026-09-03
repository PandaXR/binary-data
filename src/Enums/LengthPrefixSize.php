<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Enums;

/**
 * Supported unsigned integer widths for length-prefixed strings.
 */
enum LengthPrefixSize: int
{
    /** An unsigned 8-bit length prefix. */
    case UINT8 = 1;

    /** An unsigned 16-bit length prefix. */
    case UINT16 = 2;

    /** An unsigned 32-bit length prefix. */
    case UINT32 = 4;

    /**
     * Get the largest length representable by this prefix.
     *
     * @return int Maximum unsigned value for the selected width
     */
    public function maximumValue(): int
    {
        return match ($this) {
            self::UINT8 => 255,
            self::UINT16 => 65_535,
            self::UINT32 => 4_294_967_295,
        };
    }
}
