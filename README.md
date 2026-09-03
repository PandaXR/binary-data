# Binary Data

A Composer package for reading and writing binary data in PHP.

## Requirements

- PHP 8.2 or later
- A 64-bit PHP build (`PHP_INT_SIZE === 8`)

Composer enforces the architecture requirement through the `php-64bit` platform
package.

## Platform support

This package intentionally supports only 64-bit PHP builds. Its public API uses PHP
integers for signed 64-bit values and for the complete unsigned 32-bit range. Those
values cannot be represented correctly by native integers on a 32-bit PHP build, and
PHP's 64-bit `pack()` and `unpack()` formats are unavailable there.

Unsigned 64-bit values use the byte-backed `UInt64` value object so that the complete
range from `0` through `18446744073709551615` can be preserved without precision loss.
`UInt64::toInt()` is available only when the stored value is no greater than
`PHP_INT_MAX`; otherwise it throws an `OverflowException`. Use
`UInt64::toDecimalString()` or `UInt64::toHex()` when the value may exceed that limit.

Floating-point encoding follows PHP's `pack()` and `unpack()` formats. Because PHP
documents the sizes of `f`, `g`, `G`, `d`, `e`, and `E` as machine-dependent, the
reader derives their encoded widths from the running PHP build instead of assuming
four-byte floats and eight-byte doubles. Writer methods return the actual encoded
byte count for these values.
