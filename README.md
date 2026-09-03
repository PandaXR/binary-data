# Binary Data

[![CI](https://github.com/PandaXR/binary-data/actions/workflows/ci.yml/badge.svg)](https://github.com/PandaXR/binary-data/actions/workflows/ci.yml)
[![Documentation](https://img.shields.io/badge/docs-GitHub%20Pages-blue.svg)](https://pandaxr.github.io/binary-data/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A small, strictly typed Composer package for reading and writing binary data in PHP.

## Features

- Signed 8-, 16-, 32-, and 64-bit integers
- Unsigned 8-, 16-, 32-, and full-range 64-bit integers
- Native, little-endian, and big-endian byte order
- Platform-sized floating-point encoding
- Null-terminated, delimiter-terminated, and length-prefixed strings
- Configurable limits for untrusted variable-length input
- Exact reads and complete writes over partial-I/O streams
- File streams and contracts for custom stream implementations
- Package-specific exceptions distinguish library failures from PHP engine errors

## Requirements

- PHP 8.2 or later
- A 64-bit PHP build (`PHP_INT_SIZE === 8`)

Composer enforces the architecture requirement through the `php-64bit` platform
package.

## Installation

```bash
composer require lxr/binary-data
```

The complete guide and API reference are available on the
[documentation site](https://pandaxr.github.io/binary-data/).

## Quick start

`FileBinaryStream` opens an existing file in `r+b` mode. Create the file before opening
it if it does not already exist.

```php
<?php

use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Enums\LengthPrefixSize;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Streams\FileBinaryStream;
use Lxr\BinaryData\Writer;

$path = __DIR__ . '/message.bin';
file_put_contents($path, '');

$stream = new FileBinaryStream($path);

try {
    $writer = new Writer($stream, ByteOrder::BIG_ENDIAN);
    $writer->writeUInt16(0xcafe);
    $writer->writeInt32(-42);
    $writer->writeLengthPrefixedString('hello', LengthPrefixSize::UINT8);
    $writer->writeNullTerminatedString('done');

    $reader = new Reader($stream, ByteOrder::BIG_ENDIAN);
    $reader->setPosition(0);

    $marker = $reader->readUInt16();
    $number = $reader->readInt32();
    $message = $reader->readLengthPrefixedString(LengthPrefixSize::UINT8);
    $status = $reader->readNullTerminatedString();
} finally {
    $stream->close();
}
```

## Byte order

Pass a `ByteOrder` value to `Reader` and `Writer`:

```php
$reader = new Reader($stream, ByteOrder::LITTLE_ENDIAN);
$writer = new Writer($stream, ByteOrder::BIG_ENDIAN);
```

`ByteOrder::NATIVE` is the default. Byte order affects multi-byte integers and
floating-point values; it does not affect raw bytes or strings.

## Supported values

| Value | Reader | Writer |
| --- | --- | --- |
| Raw bytes | `readBytes()` | `writeBytes()` |
| Signed integers | `readInt8()` through `readInt64()` | `writeInt8()` through `writeInt64()` |
| Unsigned integers | `readUInt8()` through `readUInt64()` | `writeUInt8()` through `writeUInt64()` |
| Floating point | `readFloat()`, `readDouble()` | `writeFloat()`, `writeDouble()` |
| Length-prefixed strings | `readLengthPrefixedString()` | `writeLengthPrefixedString()` |
| Null-terminated strings | `readNullTerminatedString()` | `writeNullTerminatedString()` |
| Delimited bytes | `readUntil()` | Write the bytes and delimiter separately |

Fixed-width integer and raw-byte writer methods complete the write or throw. Float,
double, and string writer methods return the total encoded byte count after a successful
write. String counts include their terminator or length prefix.

## Unsigned 64-bit integers

PHP has no unsigned integer type. `readUInt64()` therefore returns an immutable,
byte-backed `UInt64` value that preserves the complete range from `0` through
`18446744073709551615`.

```php
use Lxr\BinaryData\ValueObjects\UInt64;

$maximum = new UInt64(hex2bin('ffffffffffffffff'));

$maximum->toHex();           // "ffffffffffffffff"
$maximum->toDecimalString(); // "18446744073709551615"
$maximum->toBigEndianBytes();
$maximum->toLittleEndianBytes();
```

`UInt64::toInt()` works only when the value is no greater than `PHP_INT_MAX`; otherwise
it throws `Lxr\BinaryData\Exceptions\OverflowException`.

## Strings and read limits

Variable-length parsing is bounded to reduce memory-exhaustion risk when reading
untrusted data. The default maximum is 16 MiB. Configure it through the third `Reader`
constructor argument:

```php
$reader = new Reader(
    $stream,
    ByteOrder::BIG_ENDIAN,
    maximumStringLength: 1_048_576,
);
```

- Length-prefixed reads reject a declared length above the configured maximum before
  reading the payload.
- Null-terminated reads require a terminator and reject a truncated value.
- `readUntil()` returns the remaining bytes when its delimiter is not found.
- Null-terminated writes reject values containing an embedded null byte.
- Length-prefixed writes reject values that do not fit in the selected prefix width.

Available prefix widths are `LengthPrefixSize::UINT8` (one byte),
`LengthPrefixSize::UINT16` (two bytes), and `LengthPrefixSize::UINT32` (four bytes).

## Platform support

This package intentionally supports only 64-bit PHP builds. Its API uses native PHP
integers for signed 64-bit values and for the complete unsigned 32-bit range. Those
values cannot be represented correctly by native integers on 32-bit PHP, where PHP's
64-bit `pack()` and `unpack()` formats are also unavailable.

Floating-point encoding follows PHP's `pack()` and `unpack()` formats. Because PHP
documents the sizes of `f`, `g`, `G`, `d`, `e`, and `E` as machine-dependent, the
Reader derives their widths from the running PHP build. Writer methods return the
actual encoded byte count.

## Custom streams

Implement `Lxr\BinaryData\Contracts\StreamContract` to use another storage or transport:

```php
interface StreamContract
{
    public function getPosition(): int;
    public function setPosition(int $position): void;
    public function read(int $length): string;
    public function write(string $data): int;
    public function close(): void;
}
```

`read()` may return fewer bytes at the end of the stream. `Reader` collects partial chunks
for fixed-size values and `readBytes()`, and throws if the stream ends before the requested
value is complete. `write()` may report a partial write; `Writer` continues until the
encoded value is complete and throws if the stream makes no progress. `close()` must be
idempotent and must flush buffered writes before releasing the underlying resource.

Applications can depend on `ReaderContract` and `WriterContract` when they do not need
the concrete implementations.

## Exceptions

All intentional package exceptions implement
`Lxr\BinaryData\Exceptions\BinaryDataException` while retaining their corresponding
PHP SPL exception base class:

- `InvalidArgumentException` for invalid API arguments
- `OverflowException` for values or lengths outside supported ranges
- `UnexpectedValueException` for malformed or truncated binary input
- `RuntimeException` for stream and binary-operation failures
- `LogicException` for internally inconsistent states

Catch `BinaryDataException` to handle any package-reported failure without hiding native
PHP errors such as `TypeError`.

## Development

Install the locked development dependencies and run the complete check suite:

```bash
composer install
composer check
```

Useful individual commands:

```bash
composer test
composer test:coverage
composer analyse
composer cs
composer cs:fix
```

CI runs quality checks, dependency auditing, and tests on PHP 8.2 through PHP 8.5.

## Security

Please report vulnerabilities privately according to [SECURITY.md](SECURITY.md).

## Changelog

Release history is recorded in [CHANGELOG.md](CHANGELOG.md).

## License

Binary Data is released under the [MIT License](LICENSE).
