---
layout: page
title: Usage guide
permalink: /guide/
---

## Exact reads and complete writes

Low-level streams may return fewer bytes than requested. Reader collects partial chunks
for fixed-size values and `readBytes()`. It throws `UnexpectedValueException` if the stream
ends before a complete value is available.

Writer continues partial writes until the complete encoded value is written. It throws
`RuntimeException` if the stream reports an invalid count or makes no progress.

## Unsigned 64-bit integers

PHP has no unsigned integer type. `readUInt64()` therefore returns an immutable,
byte-backed `UInt64` value that preserves values through `18446744073709551615`.

```php
use Lxr\BinaryData\ValueObjects\UInt64;

$maximum = new UInt64(hex2bin('ffffffffffffffff'));

$maximum->toHex();           // "ffffffffffffffff"
$maximum->toDecimalString(); // "18446744073709551615"
$maximum->toBigEndianBytes();
$maximum->toLittleEndianBytes();
```

`toInt()` returns a native integer only when the value is no greater than `PHP_INT_MAX`.
Larger values throw `OverflowException`.

## String formats and limits

Variable-length parsing is limited to 16 MiB by default. Choose a smaller protocol-specific
limit when parsing untrusted data:

```php
$reader = new Reader(
    $stream,
    ByteOrder::BIG_ENDIAN,
    maximumStringLength: 1_048_576,
);
```

- `readLengthPrefixedString()` rejects an excessive declared length before reading payload.
- `readNullTerminatedString()` requires a null terminator.
- `readUntil()` returns remaining bytes if its delimiter is absent.
- `writeNullTerminatedString()` rejects embedded null bytes.
- `writeLengthPrefixedString()` rejects values that do not fit the selected prefix width.

Delimiter reads use bounded chunks and preserve bytes appearing after the delimiter.

## Custom streams

Implement `StreamContract` to use another storage or transport:

```php
use Lxr\BinaryData\Contracts\StreamContract;

interface StreamContract
{
    public function getPosition(): int;
    public function setPosition(int $position): void;
    public function read(int $length): string;
    public function write(string $data): int;
    public function close(): void;
}
```

`read()` may return fewer bytes than requested. `write()` may report a partial write.
`close()` must be idempotent and flush buffered writes before releasing its resource.

## Exceptions

All intentional package exceptions implement `BinaryDataException` and retain their
corresponding PHP SPL exception base class:

- `InvalidArgumentException` for invalid API arguments
- `OverflowException` for values or lengths outside supported ranges
- `UnexpectedValueException` for malformed or truncated input
- `RuntimeException` for stream and binary-operation failures
- `LogicException` for internally inconsistent states

Catch `BinaryDataException` when an application wants to handle every package-reported
failure without hiding native PHP errors such as `TypeError`.
