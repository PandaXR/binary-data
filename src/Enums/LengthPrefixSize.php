<?php

declare(strict_types=1);

namespace Lxr\BinaryData\Enums;

/**
 * Supported unsigned integer widths for length-prefixed strings.
 */
enum LengthPrefixSize: int
{
    /** An unsigned 8-bit length prefix. */
    case BYTE = 1;

    /** An unsigned 16-bit length prefix. */
    case SHORT = 2;

    /** An unsigned 32-bit length prefix. */
    case INT = 4;

    /**
     * Get the largest length representable by this prefix.
     *
     * @return int Maximum unsigned value for the selected width
     */
    public function maximumValue(): int
    {
        return match ($this) {
            self::BYTE => 255,
            self::SHORT => 65_535,
            self::INT => 4_294_967_295,
        };
    }
}
