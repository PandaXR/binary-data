---
layout: home
title: Binary Data for PHP
---

Binary Data is a small, strictly typed Composer package for reading and writing binary
formats in PHP.

It provides predictable byte-order handling, full-range unsigned 64-bit values, bounded
string parsing, exact reads, complete writes, and package-specific exceptions.

```bash
composer require lxr/binary-data
```

```php
use Lxr\BinaryData\Enums\ByteOrder;
use Lxr\BinaryData\Reader;
use Lxr\BinaryData\Streams\FileBinaryStream;
use Lxr\BinaryData\Writer;

$stream = new FileBinaryStream($path);
$writer = new Writer($stream, ByteOrder::BIG_ENDIAN);

$writer->writeUInt16(0xcafe);
$writer->writeInt32(-42);

$reader = new Reader($stream, ByteOrder::BIG_ENDIAN);
$reader->setPosition(0);

$marker = $reader->readUInt16();
$number = $reader->readInt32();

$stream->close();
```

## Highlights

- Signed and unsigned 8-, 16-, 32-, and 64-bit values
- Native, little-endian, and big-endian byte order
- Lossless `UInt64` representation across the complete unsigned range
- Single- and double-precision floating-point values
- Raw, delimited, null-terminated, and length-prefixed strings
- Configurable limits for parsing untrusted variable-length input
- Contracts for custom files, memory buffers, or transport streams

[Get started]({{ '/getting-started/' | relative_url }}) or browse the
[API reference]({{ '/api/' | relative_url }}).
